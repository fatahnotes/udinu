<?php
// modules/submission/view-file.php — Secure file viewer for uploaded documents

$base_path = dirname(__DIR__, 2);
require_once $base_path . '/config/config.php';
require_once $base_path . '/modules/auth/functions-auth.php';
require_once __DIR__ . '/functions-submission.php';

require_login();
if ($_SESSION['user_role'] !== 'USER') {
    http_response_code(403);
    die('Forbidden');
}

$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;
$submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($file_id <= 0) {
    http_response_code(400);
    die('Invalid file ID');
}

$db = get_db_connection();

// Verify the user owns this file
$sql = "SELECT sf.* FROM submission_files sf
        JOIN submissions s ON sf.submission_id = s.id
        WHERE sf.id = ? AND s.user_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$file_id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    die('File not found or access denied');
}

$full_path = $base_path . '/' . $file['file_path'];

if (!file_exists($full_path)) {
    http_response_code(404);
    die('File not found on server');
}

// Determine content type
$mime = $file['mime_type'] ?? 'application/octet-stream';
$ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));

// Map common extensions to MIME for viewing in browser
$inline_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
$is_inline = in_array($ext, $inline_types);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full_path));
header('Content-Disposition: ' . ($is_inline ? 'inline' : 'attachment') . '; filename="' . $file['file_name'] . '"');
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

readfile($full_path);
exit;
