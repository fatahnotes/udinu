<?php
// check-errors.php — PROTECTED: SUPERADMIN only
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/modules/auth/functions-auth.php';
require_login();
if (($_SESSION['user_role'] ?? '') !== 'SUPERADMIN') {
    http_response_code(403);
    die('Akses ditolak.');
}

// Check PHP error log location
echo "PHP error_log: " . ini_get('error_log') . "<br>";

// Check if we can write to error log
$test_error = "Test error log entry " . date('Y-m-d H:i:s');
error_log($test_error);

// Try to read error log — use PHP file functions instead of shell_exec
$error_log_path = ini_get('error_log');
if (!empty($error_log_path) && file_exists($error_log_path) && is_readable($error_log_path)) {
    echo "Error log exists at: " . htmlspecialchars($error_log_path) . "<br>";
    echo "Last 50 lines of error log:<br><pre>";
    $handle = fopen($error_log_path, 'r');
    if ($handle) {
        $lines = [];
        while (($line = fgets($handle)) !== false) {
            $lines[] = htmlspecialchars($line);
            if (count($lines) > 100) array_shift($lines);
        }
        fclose($handle);
        echo implode('', array_slice($lines, -50));
    }
    echo "</pre>";
} else {
    echo "Error log file not found or not readable at: " . htmlspecialchars($error_log_path) . "<br>";
}
?>