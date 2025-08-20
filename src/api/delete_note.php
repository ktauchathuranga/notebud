<?php
// src/api/delete_note.php
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
if (!$id || !preg_match('/^[0-9a-f]{24}$/i', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid id']);
    exit;
}

$objectId = new MongoDB\BSON\ObjectId($id);
$ns = $DB_NAME . '.notes';
$res = mongo_delete_one($ns, ['_id' => $objectId, 'user_id' => $userId]);

echo json_encode(['success' => true]);