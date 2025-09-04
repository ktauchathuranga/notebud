<?php
// src/api/db.php
// MongoDB helper for Atlas cloud connection with GridFS support

// Load .env file
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

// Get database configuration
$DB_NAME = getenv('DB_NAME') ?: 'notebud';

// Try to use full MongoDB URI first (for Atlas)
$MONGODB_URI = getenv('MONGODB_URI');

if ($MONGODB_URI) {
    // Use Atlas connection string
    $uri = $MONGODB_URI;
} else {
    // Fallback to individual components (for local Docker)
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
    'serverSelectionTimeoutMS' => 5000, // 5 second timeout
    'connectTimeoutMS' => 10000,        // 10 second connection timeout
];

try {
    // Create Manager
    $manager = new MongoDB\Driver\Manager($uri, $options);

    // Test connection
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $manager->executeCommand('admin', $command);
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Cannot connect to database. Please try again later.']);
    exit;
}

// Helper functions for basic MongoDB operations
function mongo_insert_one($namespace, $doc)
{
    global $manager;
    try {
        $bulk = new MongoDB\Driver\BulkWrite();
        $id = $bulk->insert($doc);
        $result = $manager->executeBulkWrite($namespace, $bulk);
        return $id;
    } catch (Exception $e) {
        error_log("MongoDB insert error in $namespace: " . $e->getMessage());
        throw $e;
    }
}

function mongo_find($namespace, $filter = [], $options = [])
{
    global $manager;
    try {
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $manager->executeQuery($namespace, $query);
        $results = [];
        foreach ($cursor as $doc) {
            $results[] = $doc;
        }
        return $results;
    } catch (Exception $e) {
        error_log("MongoDB find error in $namespace: " . $e->getMessage());
        return [];
    }
}

function mongo_find_one($namespace, $filter = [])
{
    $res = mongo_find($namespace, $filter, ['limit' => 1]);
    return count($res) ? $res[0] : null;
}

function mongo_update_one($namespace, $filter, $update, $options = [])
{
    global $manager;
    try {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update($filter, $update, $options);
        $result = $manager->executeBulkWrite($namespace, $bulk);
        return $result;
    } catch (Exception $e) {
        error_log("MongoDB update error in $namespace: " . $e->getMessage());
        throw $e;
    }
}

function mongo_delete_one($namespace, $filter)
{
    global $manager;
    try {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete($filter, ['limit' => 1]);
        $result = $manager->executeBulkWrite($namespace, $bulk);
        return $result;
    } catch (Exception $e) {
        error_log("MongoDB delete error in $namespace: " . $e->getMessage());
        throw $e;
    }
}

function mongo_delete_many($namespace, $filter)
{
    global $manager;
    try {
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete($filter, ['limit' => 0]); // 0 means delete all matching
        $result = $manager->executeBulkWrite($namespace, $bulk);
        return $result;
    } catch (Exception $e) {
        error_log("MongoDB delete many error in $namespace: " . $e->getMessage());
        throw $e;
    }
}

function mongo_aggregate($namespace, $pipeline)
{
    global $manager;
    try {
        $command = new MongoDB\Driver\Command([
            'aggregate' => substr($namespace, strrpos($namespace, '.') + 1),
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $db_name = substr($namespace, 0, strrpos($namespace, '.'));
        $cursor = $manager->executeCommand($db_name, $command);

        $results = [];
        foreach ($cursor as $doc) {
            $results[] = $doc;
        }
        return $results;
    } catch (Exception $e) {
        error_log("MongoDB aggregate error in $namespace: " . $e->getMessage());
        return [];
    }
}

function init_database()
{
    global $manager, $DB_NAME;

    try {
        // =================
        // NOTES COLLECTION
        // =================

        // Create TTL index for notes auto-deletion (30 days = 2592000 seconds)
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'notes',
            'indexes' => [
                [
                    'key' => ['createdAt' => 1],
                    'name' => 'createdAt_ttl',
                    'expireAfterSeconds' => 2592000, // 30 days
                    'background' => true
                ],
                [
                    'key' => ['userId' => 1],
                    'name' => 'userId_index',
                    'background' => true
                ],
                [
                    'key' => ['userId' => 1, 'createdAt' => -1],
                    'name' => 'user_notes_sort_index',
                    'background' => true
                ],
                [
                    'key' => ['sharedWith' => 1],
                    'name' => 'shared_notes_index',
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // =================
        // USERS COLLECTION
        // =================

        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'users',
            'indexes' => [
                [
                    'key' => ['username' => 1],
                    'name' => 'username_unique',
                    'unique' => true,
                    'background' => true
                ],
                [
                    'key' => ['email' => 1],
                    'name' => 'email_index',
                    'sparse' => true,
                    'background' => true
                ],
                [
                    'key' => ['refresh_token' => 1],
                    'name' => 'refresh_token_index',
                    'sparse' => true,
                    'background' => true
                ],
                [
                    'key' => ['username' => 1, 'refresh_token' => 1, 'token_used' => 1],
                    'name' => 'password_reset_index',
                    'background' => true
                ],
                [
                    'key' => ['createdAt' => 1],
                    'name' => 'user_created_index',
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // ===================
        // SESSIONS COLLECTION
        // ===================

        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'sessions',
            'indexes' => [
                [
                    'key' => ['expires_at' => 1],
                    'name' => 'expires_at_ttl',
                    'expireAfterSeconds' => 0, // Expire at the specified time
                    'partialFilterExpression' => ['permanent' => false],
                    'background' => true
                ],
                [
                    'key' => ['user_id' => 1, 'session_id' => 1],
                    'name' => 'user_session_unique',
                    'unique' => true,
                    'background' => true
                ],
                [
                    'key' => ['user_id' => 1],
                    'name' => 'user_sessions_index',
                    'background' => true
                ],
                [
                    'key' => ['last_activity' => 1],
                    'name' => 'last_activity_index',
                    'background' => true
                ],
                [
                    'key' => ['session_id' => 1],
                    'name' => 'session_id_index',
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // ============================
        // CHAT COLLECTIONS (WebSocket)
        // ============================

        // Chat requests collection
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'chat_requests',
            'indexes' => [
                [
                    'key' => ['to_user_id' => 1, 'status' => 1],
                    'name' => 'chat_requests_to_user_index',
                    'background' => true
                ],
                [
                    'key' => ['from_user_id' => 1, 'status' => 1],
                    'name' => 'chat_requests_from_user_index',
                    'background' => true
                ],
                [
                    'key' => ['created_at' => 1],
                    'name' => 'chat_requests_ttl',
                    'expireAfterSeconds' => 2592000, // 30 days
                    'background' => true
                ],
                [
                    'key' => ['from_user_id' => 1, 'to_user_id' => 1],
                    'name' => 'chat_request_unique',
                    'unique' => true,
                    'partialFilterExpression' => ['status' => 'pending'],
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // Chat sessions collection
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'chat_sessions',
            'indexes' => [
                [
                    'key' => ['participants' => 1],
                    'name' => 'chat_participants_index',
                    'background' => true
                ],
                [
                    'key' => ['created_at' => 1],
                    'name' => 'chat_sessions_created_index',
                    'background' => true
                ],
                [
                    'key' => ['last_message_at' => -1],
                    'name' => 'chat_sessions_activity_index',
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // Chat messages collection
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'chat_messages',
            'indexes' => [
                [
                    'key' => ['chat_id' => 1, 'created_at' => -1],
                    'name' => 'chat_messages_chat_time_index',
                    'background' => true
                ],
                [
                    'key' => ['from_user_id' => 1],
                    'name' => 'chat_messages_from_user_index',
                    'background' => true
                ],
                [
                    'key' => ['created_at' => 1],
                    'name' => 'chat_messages_ttl',
                    'expireAfterSeconds' => 2592000, // 30 days
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // =========================
        // GRIDFS FILES COLLECTIONS
        // =========================

        // GridFS files collection (fs.files)
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'fs.files',
            'indexes' => [
                [
                    'key' => ['uploadDate' => 1],
                    'name' => 'uploadDate_ttl',
                    'expireAfterSeconds' => 2592000, // 30 days auto-delete
                    'background' => true
                ],
                [
                    'key' => ['metadata.user_id' => 1],
                    'name' => 'user_files_index',
                    'background' => true
                ],
                [
                    'key' => ['metadata.user_id' => 1, 'uploadDate' => -1],
                    'name' => 'user_files_sort_index',
                    'background' => true
                ],
                [
                    'key' => ['filename' => 1, 'metadata.user_id' => 1],
                    'name' => 'filename_user_index',
                    'background' => true
                ],
                [
                    'key' => ['metadata.mime_type' => 1],
                    'name' => 'mime_type_index',
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // GridFS chunks collection (fs.chunks)
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'fs.chunks',
            'indexes' => [
                [
                    'key' => ['files_id' => 1, 'n' => 1],
                    'name' => 'files_id_n_unique',
                    'unique' => true,
                    'background' => true
                ],
                [
                    'key' => ['files_id' => 1],
                    'name' => 'files_id_index',
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // ============================
        // SHARE REQUESTS COLLECTION
        // ============================

        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'share_requests',
            'indexes' => [
                [
                    'key' => ['to_user' => 1, 'status' => 1],
                    'name' => 'share_requests_to_user_index',
                    'background' => true
                ],
                [
                    'key' => ['from_user' => 1],
                    'name' => 'share_requests_from_user_index',
                    'background' => true
                ],
                [
                    'key' => ['note_id' => 1],
                    'name' => 'share_requests_note_index',
                    'background' => true
                ],
                [
                    'key' => ['created_at' => 1],
                    'name' => 'share_requests_ttl',
                    'expireAfterSeconds' => 2592000, // 30 days
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // ===============================
        // ONLINE USERS COLLECTION (WebSocket)
        // ===============================

        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'online_users',
            'indexes' => [
                [
                    'key' => ['user_id' => 1],
                    'name' => 'online_users_user_index',
                    'unique' => true,
                    'background' => true
                ],
                [
                    'key' => ['last_seen' => 1],
                    'name' => 'online_users_ttl',
                    'expireAfterSeconds' => 300, // 5 minutes - remove offline users
                    'background' => true
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);

        // Log successful initialization
        error_log("Database initialization completed successfully for database: $DB_NAME");
    } catch (Exception $e) {
        // Indexes might already exist, log but don't fail
        error_log("Database initialization note: " . $e->getMessage());
    }
}

// Helper function to clean up expired sessions
function cleanup_expired_sessions()
{
    global $manager, $DB_NAME;

    try {
        $sessionsNs = $DB_NAME . '.sessions';
        $bulk = new MongoDB\Driver\BulkWrite();

        // Remove temporary sessions that have expired
        $bulk->delete([
            'permanent' => false,
            'expires_at' => ['$lt' => new MongoDB\BSON\UTCDateTime()]
        ]);

        $result = $manager->executeBulkWrite($sessionsNs, $bulk);
        $deletedCount = $result->getDeletedCount();

        if ($deletedCount > 0) {
            error_log("Cleaned up {$deletedCount} expired session(s)");
        }

        return $deletedCount;
    } catch (Exception $e) {
        error_log("Error cleaning up sessions: " . $e->getMessage());
        return 0;
    }
}

// Helper function to clean up old chat data
function cleanup_old_chat_data()
{
    global $manager, $DB_NAME;

    try {
        $deleted_messages = 0;
        $deleted_requests = 0;

        // Clean up old chat messages (older than 30 days)
        $messagesNs = $DB_NAME . '.chat_messages';
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete([
            'created_at' => ['$lt' => new MongoDB\BSON\UTCDateTime(time() * 1000 - 2592000000)] // 30 days ago
        ]);
        $result = $manager->executeBulkWrite($messagesNs, $bulk);
        $deleted_messages = $result->getDeletedCount();

        // Clean up old chat requests (older than 30 days)
        $requestsNs = $DB_NAME . '.chat_requests';
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete([
            'created_at' => ['$lt' => new MongoDB\BSON\UTCDateTime(time() * 1000 - 2592000000)] // 30 days ago
        ]);
        $result = $manager->executeBulkWrite($requestsNs, $bulk);
        $deleted_requests = $result->getDeletedCount();

        if ($deleted_messages > 0 || $deleted_requests > 0) {
            error_log("Cleaned up {$deleted_messages} old chat message(s) and {$deleted_requests} old chat request(s)");
        }

        return ['messages' => $deleted_messages, 'requests' => $deleted_requests];
    } catch (Exception $e) {
        error_log("Error cleaning up chat data: " . $e->getMessage());
        return ['messages' => 0, 'requests' => 0];
    }
}

// Helper function to get database statistics
function get_database_stats()
{
    global $manager, $DB_NAME;

    try {
        $stats = [];

        // Get collection stats
        $collections = ['users', 'notes', 'sessions', 'fs.files', 'fs.chunks', 'chat_messages', 'chat_requests', 'chat_sessions'];

        foreach ($collections as $collection) {
            try {
                $command = new MongoDB\Driver\Command(['count' => $collection]);
                $cursor = $manager->executeCommand($DB_NAME, $command);
                $result = current($cursor->toArray());
                $stats[$collection] = $result ? $result->n : 0;
            } catch (Exception $e) {
                $stats[$collection] = 0;
            }
        }

        return $stats;
    } catch (Exception $e) {
        error_log("Error getting database stats: " . $e->getMessage());
        return [];
    }
}

// Helper function to check database health
function check_database_health()
{
    global $manager;

    try {
        // Test basic connectivity
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $manager->executeCommand('admin', $command);

        // Test database operations
        $command = new MongoDB\Driver\Command(['serverStatus' => 1]);
        $cursor = $manager->executeCommand('admin', $command);
        $serverStatus = current($cursor->toArray());

        return [
            'status' => 'healthy',
            'mongodb_version' => $serverStatus->version ?? 'unknown',
            'uptime' => $serverStatus->uptime ?? 0,
            'connections' => [
                'current' => $serverStatus->connections->current ?? 0,
                'available' => $serverStatus->connections->available ?? 0
            ]
        ];
    } catch (Exception $e) {
        error_log("Database health check failed: " . $e->getMessage());
        return [
            'status' => 'unhealthy',
            'error' => $e->getMessage()
        ];
    }
}

// Initialize database on first load
init_database();

// Log initialization
error_log("MongoDB connection established for database: $DB_NAME");
