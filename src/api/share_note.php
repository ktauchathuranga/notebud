<?php
// src/api/share_note.php
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

$noteId = $_POST['note_id'] ?? '';
$username = trim($_POST['username'] ?? '');

// Validate inputs
if (!$noteId || !preg_match('/^[0-9a-f]{24}$/i', $noteId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid note ID']);
    exit;
}

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

try {
    $objectId = new MongoDB\BSON\ObjectId($noteId);
    $ns = $DB_NAME . '.notes';

    // Check if note exists and belongs to user
    $note = mongo_find_one($ns, ['_id' => $objectId, 'user_id' => $userId]);
    if (!$note) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found']);
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
        echo json_encode(['error' => 'Cannot share note with yourself']);
        exit;
    }

    // Check if note is already shared with this user
    $shareRequestsNs = $DB_NAME . '.note_share_requests';
    $existingRequest = mongo_find_one($shareRequestsNs, [
        'note_id' => $noteId,
        'from_user_id' => $userId,
        'to_user_id' => (string)$targetUser->_id,
        'status' => 'pending'
    ]);

    if ($existingRequest) {
        http_response_code(409);
        echo json_encode(['error' => 'Note already shared with this user']);
        exit;
    }

    // Create share request
    $shareRequest = [
        'note_id' => $noteId,
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
        'message' => 'Note shared successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to share note: ' . $e->getMessage()]);
}
