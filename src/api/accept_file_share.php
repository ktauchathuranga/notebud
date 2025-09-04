<?php
// src/api/accept_file_share.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gridfs_env.php';
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

    // Check user's storage limits before copying
    $storage_info = get_user_storage_info_env($userId);
    $file_size = $request->file_size;

    if (($storage_info['storage_used'] + $file_size) > $storage_info['storage_limit']) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Cannot accept file - would exceed your storage limit of ' .
                $storage_info['storage_limit_formatted']
        ]);
        exit;
    }

    if ($storage_info['file_count'] >= $storage_info['file_limit']) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Cannot accept file - would exceed your file limit of ' .
                $storage_info['file_limit'] . ' files'
        ]);
        exit;
    }

    // Get the original file from GridFS
    $filesNs = $DB_NAME . '.fs.files';
    $chunksNs = $DB_NAME . '.fs.chunks';

    $originalFileId = new MongoDB\BSON\ObjectId($request->file_id);
    $originalFile = mongo_find_one($filesNs, ['_id' => $originalFileId]);

    if (!$originalFile) {
        http_response_code(404);
        echo json_encode(['error' => 'Original file not found']);
        exit;
    }

    // Create new file ID for the copy
    $newFileId = new MongoDB\BSON\ObjectId();
    $uploadDate = new MongoDB\BSON\UTCDateTime();

    // Copy file document
    $newFileDoc = [
        '_id' => $newFileId,
        'filename' => $originalFile->filename,
        'length' => $originalFile->length,
        'chunkSize' => $originalFile->chunkSize,
        'uploadDate' => $uploadDate,
        'metadata' => [
            'user_id' => $userId,
            'original_name' => $originalFile->filename,
            'mime_type' => $originalFile->metadata->mime_type,
            'uploaded_at' => $uploadDate,
            'is_shared' => true,
            'shared_by' => $request->from_username,
            'original_file_id' => $request->file_id
        ]
    ];

    // Insert new file document
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->insert($newFileDoc);
    global $manager;
    $manager->executeBulkWrite($filesNs, $bulk);

    // Copy all chunks
    $originalChunks = mongo_find(
        $chunksNs,
        ['files_id' => $originalFileId],
        ['sort' => ['n' => 1]]
    );

    $chunksBulk = new MongoDB\Driver\BulkWrite();
    foreach ($originalChunks as $chunk) {
        $newChunk = [
            'files_id' => $newFileId,
            'n' => $chunk->n,
            'data' => $chunk->data
        ];
        $chunksBulk->insert($newChunk);
    }

    if (count($originalChunks) > 0) {
        $manager->executeBulkWrite($chunksNs, $chunksBulk);
    }

    // Update the share request status
    $updateBulk = new MongoDB\Driver\BulkWrite();
    $updateBulk->update(
        ['_id' => $objectId],
        ['$set' => [
            'status' => 'accepted',
            'accepted_at' => new MongoDB\BSON\UTCDateTime()
        ]]
    );
    $manager->executeBulkWrite($shareRequestsNs, $updateBulk);

    echo json_encode([
        'success' => true,
        'message' => 'File added to your collection',
        'file_id' => (string)$newFileId
    ]);
} catch (Exception $e) {
    error_log("Accept file share error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to accept file share: ' . $e->getMessage()]);
}
