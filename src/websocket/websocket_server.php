<?php
// src/websocket/websocket_server.php
// Standalone WebSocket server for notebud chat

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($k !== '' && getenv($k) === false) putenv("$k=$v");
    }
}

// Database configuration
$DB_NAME = getenv('DB_NAME') ?: 'notebud';
$MONGODB_URI = getenv('MONGODB_URI');
$JWT_SECRET = getenv('JWT_SECRET') ?: 'replace_this_with_a_long_random_secret_here';

if ($MONGODB_URI) {
    $uri = $MONGODB_URI;
} else {
    $DB_HOST = getenv('DB_HOST') ?: 'mongo';
    $DB_PORT = getenv('DB_PORT') ?: '27017';
    $DB_USER = getenv('DB_USER');
    $DB_PASS = getenv('DB_PASS');

    $uri = "mongodb://";
    if ($DB_USER && $DB_PASS) {
        $uri .= urlencode($DB_USER) . ':' . urlencode($DB_PASS) . '@';
    }
    $uri .= $DB_HOST . ':' . $DB_PORT . '/' . $DB_NAME;

    if ($DB_USER && $DB_PASS) {
        $uri .= '?authSource=admin';
    }
}

$options = [
    'serverSelectionTimeoutMS' => 5000,
    'connectTimeoutMS' => 10000,
];

// Wait for database to be available
$maxAttempts = 30;
$attempt = 0;
while ($attempt < $maxAttempts) {
    try {
        $manager = new MongoDB\Driver\Manager($uri, $options);
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $manager->executeCommand('admin', $command);
        echo "Database connected successfully\n";
        break;
    } catch (Exception $e) {
        $attempt++;
        echo "Database connection attempt $attempt/$maxAttempts failed: " . $e->getMessage() . "\n";
        if ($attempt >= $maxAttempts) {
            echo "Failed to connect to database after $maxAttempts attempts. Exiting.\n";
            exit(1);
        }
        sleep(2);
    }
}

// JWT functions
function base64url_decode($data)
{
    $remainder = strlen($data) % 4;
    if ($remainder) $data .= str_repeat('=', 4 - $remainder);
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_decode($token, $secret)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$headerb64, $payloadb64, $sigb64] = $parts;

    // Verify signature
    $expected_sig = hash_hmac('sha256', "$headerb64.$payloadb64", $secret, true);
    $expected_sig = rtrim(strtr(base64_encode($expected_sig), '+/', '-_'), '=');

    if (!hash_equals($expected_sig, $sigb64)) return null;

    $payloadJson = base64url_decode($payloadb64);
    $payload = json_decode($payloadJson, true);
    if (!$payload) return null;
    if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;
    return $payload;
}

// Database helper functions
function mongo_find_one($namespace, $filter = [])
{
    global $manager;
    $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
    $cursor = $manager->executeQuery($namespace, $query);
    foreach ($cursor as $doc) {
        return $doc;
    }
    return null;
}

function mongo_insert_one($namespace, $doc)
{
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $id = $bulk->insert($doc);
    $result = $manager->executeBulkWrite($namespace, $bulk);
    return $id;
}

function mongo_find($namespace, $filter = [], $options = [])
{
    global $manager;
    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $manager->executeQuery($namespace, $query);
    $results = [];
    foreach ($cursor as $doc) {
        $results[] = $doc;
    }
    return $results;
}

function mongo_update_one($namespace, $filter, $update)
{
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update($filter, $update);
    return $manager->executeBulkWrite($namespace, $bulk);
}

// WebSocket server implementation using stream sockets
class WebSocketServer
{
    private $clients = [];
    private $userSockets = [];
    private $server;

    public function __construct($address = '0.0.0.0', $port = 8080)
    {
        // Create server socket with proper options
        $context = stream_context_create([
            'socket' => [
                'so_reuseport' => 1,
                'so_reuseaddr' => 1,
            ],
        ]);

        $this->server = stream_socket_server("tcp://$address:$port", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

        if (!$this->server) {
            echo "Error creating server: $errstr ($errno)\n";
            exit(1);
        }

        stream_set_blocking($this->server, false);
        echo "WebSocket server started on $address:$port\n";

        $this->run();
    }

    private function run()
    {
        while (true) {
            // Accept new connections
            $client = @stream_socket_accept($this->server, 0);
            if ($client) {
                $this->handleNewConnection($client);
            }

            // Handle existing clients
            $read = [];
            foreach ($this->clients as $clientId => $clientData) {
                $read[] = $clientData['socket'];
            }

            if (!empty($read)) {
                $write = null;
                $except = null;
                $changed = @stream_select($read, $write, $except, 0, 100000); // 100ms timeout

                if ($changed > 0) {
                    foreach ($read as $socket) {
                        $clientId = (int)$socket;
                        $this->handleClient($clientId);
                    }
                }
            }

            // Clean up disconnected clients
            $this->cleanupClients();

            // Small delay to prevent high CPU usage
            usleep(1000); // 1ms
        }
    }

    private function cleanupClients()
    {
        foreach ($this->clients as $clientId => $clientData) {
            if (feof($clientData['socket'])) {
                $this->disconnectClient($clientId);
            }
        }
    }

    private function handleNewConnection($client)
    {
        stream_set_blocking($client, false);
        stream_set_timeout($client, 5);

        $clientId = (int)$client;
        $this->clients[$clientId] = [
            'socket' => $client,
            'handshake_done' => false,
            'user_id' => null,
            'username' => null,
            'buffer' => '',
            'last_activity' => time()
        ];

        echo "New connection: $clientId\n";
    }

    private function handleClient($clientId)
    {
        if (!isset($this->clients[$clientId])) {
            return;
        }

        $client = $this->clients[$clientId];
        $socket = $client['socket'];

        // Read data from client
        $data = @fread($socket, 8192);

        if ($data === false || $data === '') {
            // Check if connection is still alive
            if (feof($socket)) {
                $this->disconnectClient($clientId);
                return;
            }
            return;
        }

        echo "Received data from client $clientId: " . strlen($data) . " bytes\n";
        $this->clients[$clientId]['buffer'] .= $data;
        $this->clients[$clientId]['last_activity'] = time();

        if (!$client['handshake_done']) {
            $this->processHandshake($clientId);
        } else {
            $this->processMessages($clientId);
        }
    }

    private function processHandshake($clientId)
    {
        $client = $this->clients[$clientId];
        $buffer = $client['buffer'];

        // Check if we have a complete HTTP request
        $headerEnd = strpos($buffer, "\r\n\r\n");
        if ($headerEnd === false) {
            echo "Handshake incomplete for client $clientId, waiting for more data\n";
            return; // Not complete yet
        }

        $headerSection = substr($buffer, 0, $headerEnd);
        $lines = explode("\r\n", $headerSection);

        // Parse the request line
        $requestLine = array_shift($lines);
        echo "Request line: $requestLine\n";

        // Parse headers
        $headers = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        echo "Headers received: " . json_encode($headers) . "\n";

        // Check for required WebSocket headers
        if (!isset($headers['Sec-WebSocket-Key'])) {
            echo "Missing Sec-WebSocket-Key header for client $clientId\n";
            // Send a proper HTTP error response
            $errorResponse = "HTTP/1.1 400 Bad Request\r\n" .
                "Content-Type: text/plain\r\n" .
                "Content-Length: 26\r\n" .
                "Connection: close\r\n\r\n" .
                "Missing WebSocket headers";
            @fwrite($client['socket'], $errorResponse);
            $this->disconnectClient($clientId);
            return;
        }

        // Validate other required headers
        $upgrade = $headers['Upgrade'] ?? '';
        $connection = $headers['Connection'] ?? '';

        if (
            strtolower($upgrade) !== 'websocket' ||
            strpos(strtolower($connection), 'upgrade') === false
        ) {
            echo "Invalid WebSocket headers for client $clientId\n";
            $errorResponse = "HTTP/1.1 400 Bad Request\r\n" .
                "Content-Type: text/plain\r\n" .
                "Content-Length: 25\r\n" .
                "Connection: close\r\n\r\n" .
                "Invalid WebSocket request";
            @fwrite($client['socket'], $errorResponse);
            $this->disconnectClient($clientId);
            return;
        }

        $key = $headers['Sec-WebSocket-Key'];
        $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $acceptKey\r\n" .
            "\r\n";

        echo "Sending handshake response to client $clientId\n";

        if (@fwrite($client['socket'], $response) === false) {
            echo "Failed to send handshake response to client $clientId\n";
            $this->disconnectClient($clientId);
            return;
        }

        $this->clients[$clientId]['handshake_done'] = true;
        // Remove the processed headers from buffer
        $this->clients[$clientId]['buffer'] = substr($buffer, $headerEnd + 4);

        echo "Handshake completed for client $clientId\n";
    }

    private function processMessages($clientId)
    {
        $client = $this->clients[$clientId];
        $buffer = $client['buffer'];

        echo "Processing messages for client $clientId, buffer length: " . strlen($buffer) . "\n";

        while (strlen($buffer) >= 2) {
            echo "Attempting to decode frame for client $clientId\n";
            $decoded = $this->decode($buffer);

            if ($decoded === false) {
                echo "Need more data for client $clientId\n";
                break; // Need more data
            }

            if ($decoded === null) {
                echo "Invalid frame from client $clientId, disconnecting\n";
                // Invalid frame, disconnect
                $this->disconnectClient($clientId);
                return;
            }

            echo "Decoded message from client $clientId: $decoded\n";

            // Remove processed data from buffer
            $frameLength = $this->getFrameLength($buffer);
            $buffer = substr($buffer, $frameLength);
            echo "Removed $frameLength bytes from buffer, remaining: " . strlen($buffer) . "\n";

            // Process the message
            $message = json_decode($decoded, true);
            if ($message) {
                echo "Valid JSON message from client $clientId: " . json_encode($message) . "\n";
                $this->processMessage($clientId, $message);
            } else {
                echo "Invalid JSON from client $clientId: $decoded\n";
            }
        }

        $this->clients[$clientId]['buffer'] = $buffer;
    }

    private function getFrameLength($data)
    {
        if (strlen($data) < 2) return 0;

        $length = ord($data[1]) & 127;
        $indexFirstMask = 2;

        if ($length == 126) {
            if (strlen($data) < 4) return 0;
            $length = unpack('n', substr($data, 2, 2))[1];
            $indexFirstMask = 4;
        } else if ($length == 127) {
            if (strlen($data) < 10) return 0;
            $length = unpack('J', substr($data, 2, 8))[1];
            $indexFirstMask = 10;
        }

        $totalLength = $indexFirstMask + 4 + $length;
        echo "Frame length calculation: payload=$length, total=$totalLength\n";
        return $totalLength;
    }

    private function decode($data)
    {
        if (strlen($data) < 2) return false;

        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);

        // Check if frame is masked (client frames should be masked)
        $masked = ($secondByte & 128) == 128;
        if (!$masked) {
            echo "Received unmasked frame from client\n";
            return null; // Client frames must be masked
        }

        $length = $secondByte & 127;
        $indexFirstMask = 2;

        if ($length == 126) {
            if (strlen($data) < 4) return false;
            $length = unpack('n', substr($data, 2, 2))[1];
            $indexFirstMask = 4;
        } else if ($length == 127) {
            if (strlen($data) < 10) return false;
            $length = unpack('J', substr($data, 2, 8))[1];
            $indexFirstMask = 10;
        }

        if (strlen($data) < $indexFirstMask + 4 + $length) return false;

        $masks = substr($data, $indexFirstMask, 4);
        $indexFirstDataByte = $indexFirstMask + 4;

        $decoded = '';
        for ($i = 0; $i < $length; $i++) {
            $decoded .= $data[$indexFirstDataByte + $i] ^ $masks[$i % 4];
        }

        return $decoded;
    }

    private function encode($data)
    {
        $encoded = chr(129); // Text frame (FIN=1, opcode=1)
        $length = strlen($data);

        if ($length <= 125) {
            $encoded .= chr($length);
        } else if ($length <= 65535) {
            $encoded .= chr(126) . pack('n', $length);
        } else {
            $encoded .= chr(127) . pack('J', $length);
        }

        return $encoded . $data;
    }

    // Rest of your methods remain the same...
    private function processMessage($clientId, $message)
    {
        global $JWT_SECRET, $DB_NAME;

        echo "Processing message from client $clientId: " . $message['type'] . "\n";

        switch ($message['type']) {
            case 'auth':
                $this->handleAuth($clientId, $message);
                break;
            case 'send_chat_request':
                $this->handleChatRequest($clientId, $message);
                break;
            case 'accept_chat_request':
                $this->handleAcceptChatRequest($clientId, $message);
                break;
            case 'decline_chat_request':
                $this->handleDeclineChatRequest($clientId, $message);
                break;
            case 'send_message':
                $this->handleSendMessage($clientId, $message);
                break;
            case 'get_chat_requests':
                echo "Handling get_chat_requests for client $clientId\n";
                $this->handleGetChatRequests($clientId);
                break;
            case 'get_active_chats':
                echo "Handling get_active_chats for client $clientId\n";
                $this->handleGetActiveChats($clientId);
                break;
            case 'get_chat_messages':
                $this->handleGetChatMessages($clientId, $message);
                break;
            default:
                echo "Unknown message type: " . $message['type'] . "\n";
        }
    }

    private function handleAuth($clientId, $message)
    {
        global $JWT_SECRET, $DB_NAME;

        $token = $message['token'] ?? null;
        if (!$token) {
            $this->sendToClient($clientId, ['type' => 'error', 'message' => 'No token provided']);
            return;
        }

        $payload = jwt_decode($token, $JWT_SECRET);
        if (!$payload || !isset($payload['user_id'])) {
            $this->sendToClient($clientId, ['type' => 'error', 'message' => 'Invalid token']);
            return;
        }

        // Get username from database
        try {
            $user = mongo_find_one($DB_NAME . '.users', ['_id' => new MongoDB\BSON\ObjectId($payload['user_id'])]);
            if (!$user) {
                $this->sendToClient($clientId, ['type' => 'error', 'message' => 'User not found']);
                return;
            }

            $this->clients[$clientId]['user_id'] = $payload['user_id'];
            $this->clients[$clientId]['username'] = $user->username;
            $this->userSockets[$payload['user_id']] = $clientId;

            // Update user online status
            mongo_update_one(
                $DB_NAME . '.users',
                ['_id' => new MongoDB\BSON\ObjectId($payload['user_id'])],
                ['$set' => ['online' => true, 'last_seen' => new MongoDB\BSON\UTCDateTime()]]
            );

            $this->sendToClient($clientId, [
                'type' => 'auth_success',
                'user_id' => $payload['user_id'],
                'username' => $user->username
            ]);

            echo "User {$user->username} authenticated as client $clientId\n";
        } catch (Exception $e) {
            echo "Auth error: " . $e->getMessage() . "\n";
            $this->sendToClient($clientId, ['type' => 'error', 'message' => 'Authentication failed']);
        }
    }

    private function handleChatRequest($clientId, $message)
    {
        global $DB_NAME;

        $fromUserId = $this->clients[$clientId]['user_id'];
        $toUsername = $message['to_username'] ?? '';

        if (!$fromUserId || !$toUsername) {
            $this->sendToClient($clientId, ['type' => 'error', 'message' => 'Invalid request']);
            return;
        }

        try {
            // Find target user
            $toUser = mongo_find_one($DB_NAME . '.users', ['username' => $toUsername]);
            if (!$toUser) {
                $this->sendToClient($clientId, ['type' => 'error', 'message' => 'User not found']);
                return;
            }

            $toUserId = (string)$toUser->_id;

            // Check if request already exists
            $existing = mongo_find_one($DB_NAME . '.chat_requests', [
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'status' => 'pending'
            ]);

            if ($existing) {
                $this->sendToClient($clientId, ['type' => 'error', 'message' => 'Chat request already sent']);
                return;
            }

            // Create chat request
            $requestDoc = [
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'from_username' => $this->clients[$clientId]['username'],
                'to_username' => $toUsername,
                'status' => 'pending',
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];

            mongo_insert_one($DB_NAME . '.chat_requests', $requestDoc);

            // Notify target user if online
            if (isset($this->userSockets[$toUserId])) {
                $this->sendToClient($this->userSockets[$toUserId], [
                    'type' => 'new_chat_request',
                    'from_username' => $this->clients[$clientId]['username'],
                    'from_user_id' => $fromUserId
                ]);
            }

            $this->sendToClient($clientId, [
                'type' => 'chat_request_sent',
                'to_username' => $toUsername
            ]);

            echo "Chat request sent from {$this->clients[$clientId]['username']} to $toUsername\n";
        } catch (Exception $e) {
            echo "Chat request error: " . $e->getMessage() . "\n";
            $this->sendToClient($clientId, ['type' => 'error', 'message' => 'Failed to send chat request']);
        }
    }

    private function handleAcceptChatRequest($clientId, $message)
    {
        global $DB_NAME;

        $userId = $this->clients[$clientId]['user_id'];
        $fromUserId = $message['from_user_id'] ?? '';

        if (!$userId || !$fromUserId) return;

        try {
            // Update request status
            mongo_update_one(
                $DB_NAME . '.chat_requests',
                ['from_user_id' => $fromUserId, 'to_user_id' => $userId],
                ['$set' => ['status' => 'accepted']]
            );

            // Create chat room
            $chatId = uniqid();
            $chatDoc = [
                'chat_id' => $chatId,
                'participants' => [$fromUserId, $userId],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'last_message_at' => new MongoDB\BSON\UTCDateTime()
            ];

            mongo_insert_one($DB_NAME . '.chats', $chatDoc);

            // Notify both users
            $this->sendToClient($clientId, [
                'type' => 'chat_accepted',
                'chat_id' => $chatId,
                'with_user' => $this->getUsernameById($fromUserId)
            ]);

            if (isset($this->userSockets[$fromUserId])) {
                $this->sendToClient($this->userSockets[$fromUserId], [
                    'type' => 'chat_accepted',
                    'chat_id' => $chatId,
                    'with_user' => $this->clients[$clientId]['username']
                ]);
            }

            echo "Chat request accepted by {$this->clients[$clientId]['username']}\n";
        } catch (Exception $e) {
            echo "Accept chat request error: " . $e->getMessage() . "\n";
        }
    }

    private function handleDeclineChatRequest($clientId, $message)
    {
        global $DB_NAME;

        $userId = $this->clients[$clientId]['user_id'];
        $fromUserId = $message['from_user_id'] ?? '';

        if (!$userId || !$fromUserId) return;

        try {
            // Update request status
            mongo_update_one(
                $DB_NAME . '.chat_requests',
                ['from_user_id' => $fromUserId, 'to_user_id' => $userId],
                ['$set' => ['status' => 'declined']]
            );

            // Notify sender if online
            if (isset($this->userSockets[$fromUserId])) {
                $this->sendToClient($this->userSockets[$fromUserId], [
                    'type' => 'chat_declined',
                    'by_user' => $this->clients[$clientId]['username']
                ]);
            }

            echo "Chat request declined by {$this->clients[$clientId]['username']}\n";
        } catch (Exception $e) {
            echo "Decline chat request error: " . $e->getMessage() . "\n";
        }
    }

    private function handleSendMessage($clientId, $message)
    {
        global $DB_NAME;

        $userId = $this->clients[$clientId]['user_id'];
        $chatId = $message['chat_id'] ?? '';
        $messageText = $message['message'] ?? '';

        if (!$userId || !$chatId || !$messageText) return;

        try {
            // Verify user is part of chat
            $chat = mongo_find_one($DB_NAME . '.chats', [
                'chat_id' => $chatId,
                'participants' => $userId
            ]);

            if (!$chat) return;

            // Save message
            $messageDoc = [
                'chat_id' => $chatId,
                'from_user_id' => $userId,
                'from_username' => $this->clients[$clientId]['username'],
                'message' => $messageText,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];

            mongo_insert_one($DB_NAME . '.chat_messages', $messageDoc);

            // Update chat last message time
            mongo_update_one(
                $DB_NAME . '.chats',
                ['chat_id' => $chatId],
                ['$set' => ['last_message_at' => new MongoDB\BSON\UTCDateTime()]]
            );

            // Send to all participants
            foreach ($chat->participants as $participantId) {
                if (isset($this->userSockets[$participantId])) {
                    $this->sendToClient($this->userSockets[$participantId], [
                        'type' => 'new_message',
                        'chat_id' => $chatId,
                        'from_user_id' => $userId,
                        'from_username' => $this->clients[$clientId]['username'],
                        'message' => $messageText,
                        'timestamp' => time()
                    ]);
                }
            }

            echo "Message sent in chat $chatId by {$this->clients[$clientId]['username']}\n";
        } catch (Exception $e) {
            echo "Send message error: " . $e->getMessage() . "\n";
        }
    }

    private function handleGetChatRequests($clientId)
    {
        global $DB_NAME;

        $userId = $this->clients[$clientId]['user_id'];
        if (!$userId) {
            echo "No user ID for client $clientId\n";
            return;
        }

        echo "Getting chat requests for user $userId\n";

        try {
            $requests = mongo_find($DB_NAME . '.chat_requests', [
                'to_user_id' => $userId,
                'status' => 'pending'
            ]);

            $requestList = [];
            foreach ($requests as $request) {
                $requestList[] = [
                    'from_user_id' => $request->from_user_id,
                    'from_username' => $request->from_username,
                    'created_at' => $request->created_at->toDateTime()->format('c')
                ];
            }

            echo "Found " . count($requestList) . " chat requests\n";

            $response = [
                'type' => 'chat_requests',
                'requests' => $requestList
            ];

            echo "Sending response: " . json_encode($response) . "\n";

            $this->sendToClient($clientId, $response);

            echo "Sent " . count($requestList) . " chat requests to client $clientId\n";
        } catch (Exception $e) {
            echo "Get chat requests error: " . $e->getMessage() . "\n";
            $this->sendToClient($clientId, ['type' => 'error', 'message' => 'Failed to get chat requests']);
        }
    }

    private function handleGetActiveChats($clientId)
    {
        global $DB_NAME;

        $userId = $this->clients[$clientId]['user_id'];
        if (!$userId) {
            echo "No user ID for client $clientId\n";
            return;
        }

        echo "Getting active chats for user $userId\n";

        try {
            $chats = mongo_find($DB_NAME . '.chats', [
                'participants' => $userId
            ], ['sort' => ['last_message_at' => -1]]);

            $chatList = [];
            foreach ($chats as $chat) {
                $otherUserId = null;
                foreach ($chat->participants as $participantId) {
                    if ($participantId !== $userId) {
                        $otherUserId = $participantId;
                        break;
                    }
                }

                if ($otherUserId) {
                    $otherUser = mongo_find_one($DB_NAME . '.users', [
                        '_id' => new MongoDB\BSON\ObjectId($otherUserId)
                    ]);

                    $chatList[] = [
                        'chat_id' => $chat->chat_id,
                        'with_user' => $otherUser->username ?? 'Unknown',
                        'with_user_id' => $otherUserId,
                        'online' => $otherUser->online ?? false,
                        'last_message_at' => $chat->last_message_at->toDateTime()->format('c')
                    ];
                }
            }

            echo "Found " . count($chatList) . " active chats\n";

            $response = [
                'type' => 'active_chats',
                'chats' => $chatList
            ];

            echo "Sending response: " . json_encode($response) . "\n";

            $this->sendToClient($clientId, $response);

            echo "Sent " . count($chatList) . " active chats to client $clientId\n";
        } catch (Exception $e) {
            echo "Get active chats error: " . $e->getMessage() . "\n";
            $this->sendToClient($clientId, ['type' => 'error', 'message' => 'Failed to get active chats']);
        }
    }

    private function handleGetChatMessages($clientId, $message)
    {
        global $DB_NAME;

        $userId = $this->clients[$clientId]['user_id'];
        $chatId = $message['chat_id'] ?? '';

        if (!$userId || !$chatId) return;

        try {
            // Verify user is part of chat
            $chat = mongo_find_one($DB_NAME . '.chats', [
                'chat_id' => $chatId,
                'participants' => $userId
            ]);

            if (!$chat) return;

            $messages = mongo_find($DB_NAME . '.chat_messages', [
                'chat_id' => $chatId
            ], [
                'sort' => ['created_at' => 1],
                'limit' => 100
            ]);

            $messageList = [];
            foreach ($messages as $msg) {
                $messageList[] = [
                    'from_user_id' => $msg->from_user_id,
                    'from_username' => $msg->from_username,
                    'message' => $msg->message,
                    'timestamp' => $msg->created_at->toDateTime()->format('c')
                ];
            }

            $this->sendToClient($clientId, [
                'type' => 'chat_messages',
                'chat_id' => $chatId,
                'messages' => $messageList
            ]);

            echo "Sent " . count($messageList) . " messages for chat $chatId to client $clientId\n";
        } catch (Exception $e) {
            echo "Get chat messages error: " . $e->getMessage() . "\n";
        }
    }

    private function getUsernameById($userId)
    {
        global $DB_NAME;
        try {
            $user = mongo_find_one($DB_NAME . '.users', [
                '_id' => new MongoDB\BSON\ObjectId($userId)
            ]);
            return $user->username ?? 'Unknown';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }

    private function sendToClient($clientId, $data)
    {
        if (!isset($this->clients[$clientId])) {
            echo "Tried to send to non-existent client $clientId\n";
            return false;
        }

        $socket = $this->clients[$clientId]['socket'];
        $message = json_encode($data);
        $encoded = $this->encode($message);

        echo "Sending to client $clientId: $message\n";

        $result = @fwrite($socket, $encoded);
        if ($result === false) {
            echo "Failed to send to client $clientId, disconnecting\n";
            $this->disconnectClient($clientId);
            return false;
        }

        echo "Successfully sent " . strlen($encoded) . " bytes to client $clientId\n";
        return true;
    }

    private function disconnectClient($clientId)
    {
        if (!isset($this->clients[$clientId])) {
            return;
        }

        $client = $this->clients[$clientId];
        $userId = $client['user_id'];
        $username = $client['username'];

        if ($userId) {
            // Update user offline status
            global $DB_NAME;
            try {
                mongo_update_one(
                    $DB_NAME . '.users',
                    ['_id' => new MongoDB\BSON\ObjectId($userId)],
                    ['$set' => ['online' => false, 'last_seen' => new MongoDB\BSON\UTCDateTime()]]
                );
            } catch (Exception $e) {
                echo "Error updating offline status: " . $e->getMessage() . "\n";
            }

            unset($this->userSockets[$userId]);
            echo "User $username (client $clientId) disconnected\n";
        } else {
            echo "Client $clientId disconnected\n";
        }

        @fclose($client['socket']);
        unset($this->clients[$clientId]);
    }
}

// Signal handling for graceful shutdown
function signalHandler($signal)
{
    echo "Received signal $signal, shutting down...\n";
    exit(0);
}

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, 'signalHandler');
    pcntl_signal(SIGINT, 'signalHandler');
}

// Start the server
new WebSocketServer('0.0.0.0', 8080);
