<?php
// src/api/get_notes.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$payload = require_auth_api();
$userId = $payload['user_id'];

$ns = $DB_NAME . '.notes';

// Find notes by user_id, sorted by createdAt desc
$cursor = mongo_find($ns, ['user_id' => $userId], ['sort' => ['createdAt' => -1]]);

// Convert BSON objects to arrays and readable fields
$notes = [];
foreach ($cursor as $doc) {
    $notes[] = [
        'id' => isset($doc->_id) ? (string)$doc->_id : null,
        'title' => $doc->title ?? '',
        'content' => $doc->content ?? '',
        'created_at' => isset($doc->createdAt) ? ($doc->createdAt->toDateTime()->format(DATE_ATOM)) : null
    ];
}

echo json_encode($notes);