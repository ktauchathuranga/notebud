<?php
// src/api/gridfs_native.php
// GridFS implementation using native MongoDB extension (no composer needed)

require_once __DIR__ . '/db.php';

// File size limits
const MAX_USER_STORAGE = 20 * 1024 * 1024; // 20MB in bytes
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB per file
const ALLOWED_MIME_TYPES = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'text/csv',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/svg+xml'
];

function get_user_storage_usage($user_id)
{
    global $manager, $DB_NAME;

    try {
        $pipeline = [
            [
                '$match' => [
                    'metadata.user_id' => $user_id
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'total_size' => ['$sum' => '$length']
                ]
            ]
        ];

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'fs.files',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $manager->executeCommand($DB_NAME, $command);
        $result = current($cursor->toArray());

        return $result ? (int)$result->total_size : 0;
    } catch (Exception $e) {
        error_log("Error getting user storage usage: " . $e->getMessage());
        return 0;
    }
}

function upload_file($user_id, $file_data, $filename, $mime_type)
{
    global $manager, $DB_NAME;

    // Check file size
    $file_size = strlen($file_data);
    if ($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit'];
    }

    // Check MIME type
    if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    // Check user storage quota
    $current_usage = get_user_storage_usage($user_id);
    if (($current_usage + $file_size) > MAX_USER_STORAGE) {
        $remaining = MAX_USER_STORAGE - $current_usage;
        return ['success' => false, 'error' => "Storage quota exceeded. You have " . format_bytes($remaining) . " remaining"];
    }

    try {
        // Generate file ID
        $file_id = new MongoDB\BSON\ObjectId();
        $chunk_size = 261120; // 255KB chunks (GridFS default)
        $upload_date = new MongoDB\BSON\UTCDateTime();

        // Create file document
        $file_doc = [
            '_id' => $file_id,
            'filename' => $filename,
            'length' => $file_size,
            'chunkSize' => $chunk_size,
            'uploadDate' => $upload_date,
            'metadata' => [
                'user_id' => $user_id,
                'original_name' => $filename,
                'mime_type' => $mime_type,
                'uploaded_at' => $upload_date
            ]
        ];

        // Insert file document
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($file_doc);
        $manager->executeBulkWrite($DB_NAME . '.fs.files', $bulk);

        // Split file into chunks
        $chunk_num = 0;
        $chunks_bulk = new MongoDB\Driver\BulkWrite();

        for ($i = 0; $i < $file_size; $i += $chunk_size) {
            $chunk_data = substr($file_data, $i, $chunk_size);

            $chunk_doc = [
                'files_id' => $file_id,
                'n' => $chunk_num,
                'data' => new MongoDB\BSON\Binary($chunk_data, MongoDB\BSON\Binary::TYPE_GENERIC)
            ];

            $chunks_bulk->insert($chunk_doc);
            $chunk_num++;
        }

        // Insert all chunks
        $manager->executeBulkWrite($DB_NAME . '.fs.chunks', $chunks_bulk);

        return [
            'success' => true,
            'file_id' => (string)$file_id,
            'filename' => $filename,
            'size' => $file_size,
            'mime_type' => $mime_type
        ];
    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to upload file: ' . $e->getMessage()];
    }
}

function get_user_files($user_id)
{
    global $manager, $DB_NAME;

    try {
        $filter = ['metadata.user_id' => $user_id];
        $options = [
            'sort' => ['uploadDate' => -1],
            'limit' => 100
        ];

        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $manager->executeQuery($DB_NAME . '.fs.files', $query);

        $files = [];
        foreach ($cursor as $file) {
            $upload_date = $file->uploadDate;
            if ($upload_date instanceof MongoDB\BSON\UTCDateTime) {
                $upload_date = $upload_date->toDateTime()->format('Y-m-d H:i:s');
            } else {
                $upload_date = date('Y-m-d H:i:s');
            }

            $files[] = [
                'file_id' => (string)$file->_id,
                'filename' => $file->filename,
                'size' => (int)$file->length,
                'mime_type' => $file->metadata->mime_type ?? 'application/octet-stream',
                'uploaded_at' => $upload_date
            ];
        }

        return $files;
    } catch (Exception $e) {
        error_log("Error getting user files: " . $e->getMessage());
        return [];
    }
}

function download_file($file_id, $user_id)
{
    global $manager, $DB_NAME;

    try {
        // First, verify the file belongs to the user and get file info
        $filter = [
            '_id' => new MongoDB\BSON\ObjectId($file_id),
            'metadata.user_id' => $user_id
        ];

        $query = new MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery($DB_NAME . '.fs.files', $query);
        $file_doc = current($cursor->toArray());

        if (!$file_doc) {
            return ['success' => false, 'error' => 'File not found or access denied'];
        }

        // Get all chunks for this file
        $chunks_filter = ['files_id' => new MongoDB\BSON\ObjectId($file_id)];
        $chunks_options = ['sort' => ['n' => 1]];

        $chunks_query = new MongoDB\Driver\Query($chunks_filter, $chunks_options);
        $chunks_cursor = $manager->executeQuery($DB_NAME . '.fs.chunks', $chunks_query);

        // Reconstruct file from chunks
        $file_data = '';
        foreach ($chunks_cursor as $chunk) {
            if ($chunk->data instanceof MongoDB\BSON\Binary) {
                $file_data .= $chunk->data->getData();
            }
        }

        return [
            'success' => true,
            'data' => $file_data,
            'filename' => $file_doc->filename,
            'mime_type' => $file_doc->metadata->mime_type ?? 'application/octet-stream',
            'size' => (int)$file_doc->length
        ];
    } catch (Exception $e) {
        error_log("File download error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to download file'];
    }
}

function delete_file($file_id, $user_id)
{
    global $manager, $DB_NAME;

    try {
        // First, verify the file belongs to the user
        $filter = [
            '_id' => new MongoDB\BSON\ObjectId($file_id),
            'metadata.user_id' => $user_id
        ];

        $query = new MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery($DB_NAME . '.fs.files', $query);
        $file_doc = current($cursor->toArray());

        if (!$file_doc) {
            return ['success' => false, 'error' => 'File not found or access denied'];
        }

        // Delete file document
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete(['_id' => new MongoDB\BSON\ObjectId($file_id)]);
        $manager->executeBulkWrite($DB_NAME . '.fs.files', $bulk);

        // Delete all chunks
        $chunks_bulk = new MongoDB\Driver\BulkWrite();
        $chunks_bulk->delete(['files_id' => new MongoDB\BSON\ObjectId($file_id)]);
        $manager->executeBulkWrite($DB_NAME . '.fs.chunks', $chunks_bulk);

        return ['success' => true, 'message' => 'File deleted successfully'];
    } catch (Exception $e) {
        error_log("File deletion error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete file'];
    }
}

function format_bytes($size, $precision = 2)
{
    if ($size <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = (int)floor(log($size, 1024));
    $base = max(0, min($base, count($units) - 1));

    $value = round($size / pow(1024, $base), $precision);

    return $value . ' ' . $units[$base];
}

function get_file_icon($mime_type)
{
    $icons = [
        'application/pdf' => '📄',
        'application/msword' => '📝',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '📝',
        'application/vnd.ms-excel' => '📊',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '📊',
        'application/vnd.ms-powerpoint' => '📽️',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '📽️',
        'text/plain' => '📄',
        'text/csv' => '📊',
        'image/jpeg' => '🖼️',
        'image/png' => '🖼️',
        'image/gif' => '🖼️',
        'image/webp' => '🖼️',
        'image/svg+xml' => '🎨'
    ];

    return $icons[$mime_type] ?? '📎';
}

// Test function
function test_native_gridfs()
{
    global $manager, $DB_NAME;

    try {
        // Test if we can create the required collections
        $command = new MongoDB\Driver\Command(['ping' => 1]);
        $manager->executeCommand('admin', $command);

        return [
            'success' => true,
            'message' => 'Native GridFS implementation ready',
            'mongodb_extension' => extension_loaded('mongodb') ? phpversion('mongodb') : 'not loaded',
            'php_version' => PHP_VERSION
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Native GridFS test failed: ' . $e->getMessage()
        ];
    }
}
