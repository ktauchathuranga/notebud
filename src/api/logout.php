<?php
// src/api/logout.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

// Get current session info before removing cookie
$sessionInfo = get_current_session_info();

// Remove cookie
remove_cookie();

// Remove session from database if we have session info
if ($sessionInfo && isset($sessionInfo['session_id'])) {
    try {
        $sessionsNs = $DB_NAME . '.sessions';
        global $manager;
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->delete(['session_id' => $sessionInfo['session_id']]);
        $manager->executeBulkWrite($sessionsNs, $bulk);
    } catch (Exception $e) {
        error_log("Error cleaning up session: " . $e->getMessage());
        // Continue anyway, cookie is already removed
    }
}

echo json_encode(['success' => true]);
?>
