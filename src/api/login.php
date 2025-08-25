<?php
// src/api/login.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$isPermanent = isset($_POST['permanent']) && $_POST['permanent'] === 'on';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password required']);
    exit;
}

$ns = $DB_NAME . '.users';
$user = mongo_find_one($ns, ['username' => $username]);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// $user is BSON document (stdClass)
$hash = $user->password_hash ?? null;
if (!$hash || !password_verify($password, $hash)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Create token with user_id and session type
$userId = (string)$user->_id;
$sessionId = bin2hex(random_bytes(16)); // Generate unique session ID
$payload = [
    'user_id' => $userId,
    'session_id' => $sessionId,
    'permanent' => $isPermanent
];

// Store session information in database
$sessionDoc = [
    'user_id' => $userId,
    'session_id' => $sessionId,
    'permanent' => $isPermanent,
    'created_at' => new MongoDB\BSON\UTCDateTime(),
    'last_activity' => new MongoDB\BSON\UTCDateTime()
];

// If permanent session, don't set TTL. If temporary, set TTL for 4 hours
if (!$isPermanent) {
    $sessionDoc['expires_at'] = new MongoDB\BSON\UTCDateTime((time() + 4 * 60 * 60) * 1000);
}

$sessionsNs = $DB_NAME . '.sessions';
mongo_insert_one($sessionsNs, $sessionDoc);

set_auth_cookie($payload, $isPermanent);

echo json_encode(['success' => true, 'permanent' => $isPermanent]);
?>
