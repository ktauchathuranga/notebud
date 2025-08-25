<?php
// src/api/auth.php
// Helpers to authenticate using JWT stored in HttpOnly cookie

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/db.php';

$JWT_SECRET = getenv('JWT_SECRET') ?: 'replace_this_with_a_long_random_secret_here';
$COOKIE_SECURE = (strtolower(getenv('COOKIE_SECURE') ?: 'false') === 'true');

function require_auth_or_redirect() {
    global $JWT_SECRET, $COOKIE_SECURE, $DB_NAME;
    $token = $_COOKIE['token'] ?? null;
    if (!$token) {
        header('Location: /login.html');
        exit;
    }
    $payload = jwt_decode($token, $JWT_SECRET);
    if (!$payload || !isset($payload['user_id']) || !isset($payload['session_id'])) {
        // invalid token
        remove_cookie();
        header('Location: /login.html');
        exit;
    }
    
    // Check if session exists and is valid
    $sessionsNs = $DB_NAME . '.sessions';
    $session = mongo_find_one($sessionsNs, [
        'user_id' => $payload['user_id'],
        'session_id' => $payload['session_id']
    ]);
    
    if (!$session) {
        remove_cookie();
        header('Location: /login.html');
        exit;
    }
    
    // Check if temporary session has expired
    if (!($session->permanent ?? false) && isset($session->expires_at)) {
        $expiresAt = $session->expires_at->toDateTime()->getTimestamp();
        if (time() > $expiresAt) {
            // Session expired, remove it
            global $manager;
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete(['session_id' => $payload['session_id']]);
            $manager->executeBulkWrite($sessionsNs, $bulk);
            
            remove_cookie();
            header('Location: /login.html');
            exit;
        }
    }
    
    // Update last activity
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['session_id' => $payload['session_id']],
        ['$set' => ['last_activity' => new MongoDB\BSON\UTCDateTime()]]
    );
    $manager->executeBulkWrite($sessionsNs, $bulk);
    
    return $payload;
}

function require_auth_api() {
    global $JWT_SECRET, $COOKIE_SECURE, $DB_NAME;
    $token = $_COOKIE['token'] ?? null;
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    $payload = jwt_decode($token, $JWT_SECRET);
    if (!$payload || !isset($payload['user_id']) || !isset($payload['session_id'])) {
        remove_cookie();
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    
    // Check if session exists and is valid
    $sessionsNs = $DB_NAME . '.sessions';
    $session = mongo_find_one($sessionsNs, [
        'user_id' => $payload['user_id'],
        'session_id' => $payload['session_id']
    ]);
    
    if (!$session) {
        remove_cookie();
        http_response_code(401);
        echo json_encode(['error' => 'Session expired']);
        exit;
    }
    
    // Check if temporary session has expired
    if (!($session->permanent ?? false) && isset($session->expires_at)) {
        $expiresAt = $session->expires_at->toDateTime()->getTimestamp();
        if (time() > $expiresAt) {
            // Session expired, remove it
            global $manager;
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->delete(['session_id' => $payload['session_id']]);
            $manager->executeBulkWrite($sessionsNs, $bulk);
            
            remove_cookie();
            http_response_code(401);
            echo json_encode(['error' => 'Session expired']);
            exit;
        }
    }
    
    // Update last activity
    global $manager;
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->update(
        ['session_id' => $payload['session_id']],
        ['$set' => ['last_activity' => new MongoDB\BSON\UTCDateTime()]]
    );
    $manager->executeBulkWrite($sessionsNs, $bulk);
    
    return $payload;
}

// Helper to set JWT cookie after successful login
function set_auth_cookie($payload, $isPermanent = false) {
    global $JWT_SECRET, $COOKIE_SECURE;
    
    // For permanent sessions, set a very long expiration (1 year)
    // For temporary sessions, set 4 hours
    $expSeconds = $isPermanent ? (365 * 24 * 60 * 60) : (4 * 60 * 60);
    
    $token = jwt_encode($payload, $JWT_SECRET, $expSeconds);
    
    // Set HttpOnly cookie
    setcookie('token', $token, [
        'expires' => time() + $expSeconds,
        'httponly' => true,
        'secure' => $COOKIE_SECURE,
        'samesite' => 'Lax',
        'path' => '/'
    ]);
}

function remove_cookie() {
    global $COOKIE_SECURE;
    setcookie('token', '', [
        'expires' => time() - 3600,
        'httponly' => true,
        'secure' => $COOKIE_SECURE,
        'samesite' => 'Lax',
        'path' => '/'
    ]);
}

function get_current_session_info() {
    global $JWT_SECRET;
    $token = $_COOKIE['token'] ?? null;
    if (!$token) return null;
    
    $payload = jwt_decode($token, $JWT_SECRET);
    return $payload;
}
?>
