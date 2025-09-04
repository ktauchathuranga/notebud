<?php
// src/api/share_file.php
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
$fileId = $input['file_id'] ?? '';
$username = trim($input['username'] ?? '');

// Validate inputs
if (!$fileId || !preg_match('/^[0-9a-f]{24}$/i', $fileId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file ID']);
    exit;
}

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

try {
    $objectId = new MongoDB\BSON\ObjectId($fileId);
    $filesNs = $DB_NAME . '.fs.files';

    // Check if file exists and belongs to user
    $file = mongo_find_one($filesNs, [
        '_id' => $objectId,
        'metadata.user_id' => $userId
    ]);

    if (!$file) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found or access denied']);
        exit;
    }

    // Check if target user exists
    $usersNs = $DB_NAME . '.users';
    $targetUser = mongo_find_one($usersNs, ['username' => $username]);
    if (!$targetUser) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Don't allow sharing with yourself
    if ((string)$targetUser->_id === $userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot share file with yourself']);
        exit;
    }

    // Check if file is already shared with this user
    $shareRequestsNs = $DB_NAME . '.file_share_requests';
    $existingRequest = mongo_find_one($shareRequestsNs, [
        'file_id' => $fileId,
        'from_user_id' => $userId,
        'to_user_id' => (string)$targetUser->_id,
        'status' => 'pending'
    ]);

    if ($existingRequest) {
        http_response_code(409);
        echo json_encode(['error' => 'File already shared with this user']);
        exit;
    }

    // Create share request
    $shareRequest = [
        'file_id' => $fileId,
        'filename' => $file->filename,
        'file_size' => $file->length,
        'mime_type' => $file->metadata->mime_type ?? 'application/octet-stream',
        'from_user_id' => $userId,
        'from_username' => $payload['username'],
        'to_user_id' => (string)$targetUser->_id,
        'to_username' => $username,
        'status' => 'pending',
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    $requestId = mongo_insert_one($shareRequestsNs, $shareRequest);

    echo json_encode([
        'success' => true,
        'message' => 'File shared successfully'
    ]);
} catch (Exception $e) {
    error_log("Share file error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to share file: ' . $e->getMessage()]);
}
