<?php
// src/api/logout_all_temp.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$payload = require_auth_api();
$userId = $payload['user_id'];
$isPermanent = $payload['permanent'] ?? false;

// Only permanent sessions can logout all temporary sessions
if (!$isPermanent) {
    http_response_code(403);
    echo json_encode(['error' => 'Only permanent sessions can logout all temporary sessions']);
    exit;
}

try {
    $sessionsNs = $DB_NAME . '.sessions';

    // Delete all temporary sessions for this user (except current session)
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete([
        'user_id' => $userId,
        'permanent' => false,
        'session_id' => ['$ne' => $payload['session_id']]
    ]);

    $result = $manager->executeBulkWrite($sessionsNs, $bulk);
    $deletedCount = $result->getDeletedCount();

    echo json_encode([
        'success' => true,
        'message' => "Logged out {$deletedCount} temporary session(s)"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to logout sessions: ' . $e->getMessage()]);
}
