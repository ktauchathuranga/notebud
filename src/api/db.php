<?php
// src/api/db.php
// Minimal MongoDB helper using the MongoDB PHP extension (MongoDB\Driver)

// Load .env (only used if getenv doesn't have values) - keep for compatibility
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

// Prefer getenv (env_file in docker-compose will populate these)
$DB_HOST = getenv('DB_HOST') ?: 'mongo';
$DB_PORT = getenv('DB_PORT') ?: '27017';
$DB_NAME = getenv('DB_NAME') ?: 'scratchpad';
$DB_USER = getenv('DB_USER') ?: getenv('MONGO_INITDB_ROOT_USERNAME') ?: null;
$DB_PASS = getenv('DB_PASS') ?: getenv('MONGO_INITDB_ROOT_PASSWORD') ?: null;

// Build URI. If credentials present, include them and set authSource=admin (root user created in admin)
$uri = "mongodb://";
if ($DB_USER && $DB_PASS) {
    $uri .= urlencode($DB_USER) . ':' . urlencode($DB_PASS) . '@';
}
$uri .= $DB_HOST . ':' . $DB_PORT . '/' . $DB_NAME;

$options = [];
if ($DB_USER && $DB_PASS) {
    // root user is created in admin database by MONGO_INITDB_ROOT_USERNAME/PASSWORD,
    // authSource=admin ensures authentication happens against admin
    $uri .= '?authSource=admin';
    // Optionally set a serverSelectionTimeoutMS to avoid long blocking times
    $options['serverSelectionTimeoutMS'] = 3000;
}

try {
    // Create Manager
    $manager = new MongoDB\Driver\Manager($uri, $options);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Cannot connect to database: ' . $e->getMessage()]);
    exit;
}

// Helper wrappers
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

