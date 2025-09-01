<?php
// src/api/accept_share.php
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

$requestId = $_POST['request_id'] ?? '';
if (!$requestId || !preg_match('/^[0-9a-f]{24}$/i', $requestId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request ID']);
    exit;
}

try {
    $objectId = new MongoDB\BSON\ObjectId($requestId);
    $shareRequestsNs = $DB_NAME . '.note_share_requests';

    // Get the share request
    $request = mongo_find_one($shareRequestsNs, [
        '_id' => $objectId,
        'to_user_id' => $userId,
        'status' => 'pending'
    ]);

    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => 'Share request not found']);
        exit;
    }

    // Get the original note
    $notesNs = $DB_NAME . '.notes';
    $noteId = new MongoDB\BSON\ObjectId($request->note_id);
    $note = mongo_find_one($notesNs, ['_id' => $noteId]);

    if (!$note) {
        http_response_code(404);
        echo json_encode(['error' => 'Original note not found']);
        exit;
    }

    // Create a copy of the note for the current user
    $sharedNote = [
        'user_id' => $userId,
        'title' => $note->title,
        'content' => $note->content,
        'is_shared' => true,
        'shared_by' => $request->from_username,
        'original_note_id' => $request->note_id,
        'createdAt' => new MongoDB\BSON\UTCDateTime()
    ];

    $newNoteId = mongo_insert_one($notesNs, $sharedNote);

    // Update the share request status
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['_id' => $objectId],
        ['$set' => [
            'status' => 'accepted',
            'accepted_at' => new MongoDB\BSON\UTCDateTime()
        ]]
    );

    $result = $manager->executeBulkWrite($shareRequestsNs, $bulk);

    echo json_encode([
        'success' => true,
        'message' => 'Note added to your collection'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to accept share: ' . $e->getMessage()]);
}
