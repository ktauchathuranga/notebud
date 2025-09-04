<?php
// src/api/reject_file_share.php
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

$input = json_decode(file_get_contents('php://input'), true);
$requestId = $input['request_id'] ?? '';

if (!$requestId || !preg_match('/^[0-9a-f]{24}$/i', $requestId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request ID']);
    exit;
}

try {
    $objectId = new MongoDB\BSON\ObjectId($requestId);
    $shareRequestsNs = $DB_NAME . '.file_share_requests';

    // Update the share request status
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['_id' => $objectId, 'to_user_id' => $userId, 'status' => 'pending'],
        ['$set' => [
            'status' => 'rejected',
            'rejected_at' => new MongoDB\BSON\UTCDateTime()
        ]]
    );

    $result = $manager->executeBulkWrite($shareRequestsNs, $bulk);

    if ($result->getModifiedCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Share request not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'File share request rejected'
    ]);
} catch (Exception $e) {
    error_log("Reject file share error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reject share: ' . $e->getMessage()]);
}
