<?php
// src/api/reset_password.php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$refreshToken = trim($_POST['refresh_token'] ?? '');
$newPassword = $_POST['new_password'] ?? '';

if ($username === '' || $refreshToken === '' || $newPassword === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username, refresh token, and new password are required']);
    exit;
}

if (strlen($username) > 150 || strlen($newPassword) > 1024 || strlen($refreshToken) > 200) {
    http_response_code(400);
    echo json_encode(['error' => 'Input too long']);
    exit;
}

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'New password must be at least 6 characters long']);
    exit;
}

$ns = $DB_NAME . '.users';

// Find user by username and refresh token
$user = mongo_find_one($ns, [
    'username' => $username,
    'refresh_token' => $refreshToken,
    'token_used' => false
]);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid username or refresh token, or token already used']);
    exit;
}

// Generate new refresh token
$newRefreshToken = bin2hex(random_bytes(32));
$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();

    // Update password, mark old token as used, and generate new token
    $bulk->update(
        ['_id' => $user->_id],
        [
            '$set' => [
                'password_hash' => $newPasswordHash,
                'refresh_token' => $newRefreshToken,
                'token_used' => false,
                'password_reset_at' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );

    $result = $manager->executeBulkWrite($ns, $bulk);

    if ($result->getModifiedCount() === 0) {
        throw new Exception('Failed to update password');
    }

    // Also invalidate all existing sessions for this user for security
    $sessionsNs = $DB_NAME . '.sessions';
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete(['user_id' => (string)$user->_id]);
    $manager->executeBulkWrite($sessionsNs, $bulk);

    echo json_encode([
        'success' => true,
        'refresh_token' => $newRefreshToken,
        'message' => 'Password reset successfully! Here is your new refresh token. All existing sessions have been logged out for security.'
    ]);
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reset password. Please try again.']);
}
