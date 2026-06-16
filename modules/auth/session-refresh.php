<?php
require_once __DIR__ . '/../../config/config.php';

// This is an AJAX endpoint to refresh session
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Refresh session activity
$_SESSION['last_activity'] = time();
log_activity('SESSION_REFRESHED', "User refreshed session", $_SESSION['user_id']);

echo json_encode(['success' => true, 'timestamp' => date('Y-m-d H:i:s')]);
?>