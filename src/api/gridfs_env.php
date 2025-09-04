<?php
// src/api/gridfs_env.php
// Environment-controlled file upload limits

require_once __DIR__ . '/gridfs_native.php';

// Get limits from environment variables with fallbacks
function get_max_user_storage()
{
    return (int)(getenv('MAX_USER_STORAGE') ?: (20 * 1024 * 1024)); // Default 20MB
}

function get_max_file_size()
{
    return (int)(getenv('MAX_FILE_SIZE') ?: (5 * 1024 * 1024)); // Default 5MB
}

function get_max_files_per_user()
{
    return (int)(getenv('MAX_FILES_PER_USER') ?: 100); // Default 100 files
}

// Override the constants from the original file
if (!defined('MAX_USER_STORAGE')) {
    define('MAX_USER_STORAGE', get_max_user_storage());
}

if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', get_max_file_size());
}

if (!defined('MAX_FILES_PER_USER')) {
    define('MAX_FILES_PER_USER', get_max_files_per_user());
}

function upload_file_env($user_id, $file_data, $filename, $mime_type)
{
    // Check file size against env limit
    $file_size = strlen($file_data);
    $max_file_size = get_max_file_size();

    if ($file_size > $max_file_size) {
        return [
            'success' => false,
            'error' => 'File size exceeds limit of ' . format_bytes($max_file_size)
        ];
    }

    // Check MIME type
    if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    // Check user storage quota against env limit
    $current_usage = get_user_storage_usage($user_id);
    $max_storage = get_max_user_storage();

    if (($current_usage + $file_size) > $max_storage) {
        $remaining = $max_storage - $current_usage;
        return [
            'success' => false,
            'error' => "Storage quota exceeded. You have " . format_bytes($remaining) . " remaining out of " . format_bytes($max_storage)
        ];
    }

    // Check file count limit
    $current_file_count = count(get_user_files($user_id));
    $max_files = get_max_files_per_user();

    if ($current_file_count >= $max_files) {
        return [
            'success' => false,
            'error' => "Maximum number of files reached ($max_files files)"
        ];
    }

    // Use the original upload function
    return upload_file($user_id, $file_data, $filename, $mime_type);
}

function get_user_storage_info_env($user_id)
{
    $max_storage = get_max_user_storage();
    $max_file_size = get_max_file_size();
    $max_files = get_max_files_per_user();

    $usage = get_user_storage_usage($user_id);
    $files = get_user_files($user_id);

    return [
        'storage_used' => $usage,
        'storage_limit' => $max_storage,
        'storage_used_formatted' => format_bytes($usage),
        'storage_limit_formatted' => format_bytes($max_storage),
        'storage_percentage' => $max_storage > 0 ? round(($usage / $max_storage) * 100, 1) : 0,
        'file_count' => count($files),
        'file_limit' => $max_files,
        'max_file_size' => $max_file_size,
        'max_file_size_formatted' => format_bytes($max_file_size)
    ];
}

function get_current_limits()
{
    return [
        'max_user_storage' => get_max_user_storage(),
        'max_file_size' => get_max_file_size(),
        'max_files_per_user' => get_max_files_per_user(),
        'max_user_storage_formatted' => format_bytes(get_max_user_storage()),
        'max_file_size_formatted' => format_bytes(get_max_file_size())
    ];
}
