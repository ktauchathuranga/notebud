<?php
// src/api/update_note.php
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

$id = $_POST['id'] ?? '';
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

// Validate note ID
if (!$id || !preg_match('/^[0-9a-f]{24}$/i', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid note ID']);
    exit;
}

// Validate input lengths
if (strlen($title) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'Title too long']);
    exit;
}
if (strlen($content) > 20000) {
    http_response_code(400);
    echo json_encode(['error' => 'Content too long']);
    exit;
}

// Content is required
if (empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Content cannot be empty']);
    exit;
}

try {
    $objectId = new MongoDB\BSON\ObjectId($id);
    $ns = $DB_NAME . '.notes';

    // First check if the note exists and belongs to the user
    $existingNote = mongo_find_one($ns, ['_id' => $objectId, 'user_id' => $userId]);
    if (!$existingNote) {
        http_response_code(404);
        echo json_encode(['error' => 'Note not found or access denied']);
        exit;
    }

    // Update the note
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['_id' => $objectId, 'user_id' => $userId],
        [
            '$set' => [
                'title' => $title,
                'content' => $content,
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );

    $result = $manager->executeBulkWrite($ns, $bulk);

    if ($result->getModifiedCount() === 0) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update note']);
        exit;
    }

    echo json_encode(['success' => true, 'note_id' => $id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
