<?php
// src/api/save_note.php
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

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

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

// Insert note with createdAt as UTCDateTime (for TTL index)
$doc = [
    'user_id' => $userId,
    'title' => $title,
    'content' => $content,
    'createdAt' => new MongoDB\BSON\UTCDateTime()
];

$ns = $DB_NAME . '.notes';
$id = mongo_insert_one($ns, $doc);

echo json_encode(['success' => true, 'note_id' => (string)$id]);