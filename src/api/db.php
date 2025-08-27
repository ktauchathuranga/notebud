<?php
// src/api/db.php
// MongoDB helper for Atlas cloud connection

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
$DB_NAME = getenv('DB_NAME') ?: 'scratchpad';

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

// Helper functions (same as before)
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

function mongo_find_one($namespace, $filter = [])
{
    $res = mongo_find($namespace, $filter, ['limit' => 1]);
    return count($res) ? $res[0] : null;
}

function mongo_delete_one($namespace, $filter)
{
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete($filter, ['limit' => 1]);
    $result = $manager->executeBulkWrite($namespace, $bulk);
    return $result;
}

function init_database()
{
    global $manager, $DB_NAME;

    try {
        // Create TTL index for notes auto-deletion (30 days = 2592000 seconds)
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'notes',
            'indexes' => [
                [
                    'key' => ['createdAt' => 1],
                    'name' => 'createdAt_ttl',
                    'expireAfterSeconds' => 2592000 // 30 days
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);
        
        // Create TTL index for temporary sessions auto-cleanup
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'sessions',
            'indexes' => [
                [
                    'key' => ['expires_at' => 1],
                    'name' => 'expires_at_ttl',
                    'expireAfterSeconds' => 0, // Expire at the specified time
                    'partialFilterExpression' => ['permanent' => false]
                ],
                [
                    'key' => ['user_id' => 1, 'session_id' => 1],
                    'name' => 'user_session_unique',
                    'unique' => true
                ],
                [
                    'key' => ['user_id' => 1],
                    'name' => 'user_sessions_index'
                ],
                [
                    'key' => ['last_activity' => 1],
                    'name' => 'last_activity_index'
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);
        
        // Create indexes on users collection
        $command = new MongoDB\Driver\Command([
            'createIndexes' => 'users',
            'indexes' => [
                [
                    'key' => ['username' => 1],
                    'name' => 'username_unique',
                    'unique' => true
                ],
                [
                    'key' => ['refresh_token' => 1],
                    'name' => 'refresh_token_index'
                ],
                [
                    'key' => ['username' => 1, 'refresh_token' => 1, 'token_used' => 1],
                    'name' => 'password_reset_index'
                ]
            ]
        ]);
        $manager->executeCommand($DB_NAME, $command);
        
    } catch (Exception $e) {
        // Indexes might already exist, ignore error
        error_log("Index creation note: " . $e->getMessage());
    }
}

// Helper function to clean up expired sessions (can be called periodically)
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

// Initialize database on first load
init_database();
