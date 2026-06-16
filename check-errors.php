<?php
// check-errors.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check PHP error log location
echo "PHP error_log: " . ini_get('error_log') . "<br>";

// Check if we can write to error log
$test_error = "Test error log entry " . date('Y-m-d H:i:s');
error_log($test_error);

// Try to read error log
$error_log_path = ini_get('error_log');
if (file_exists($error_log_path)) {
    echo "Error log exists at: $error_log_path<br>";
    echo "Last 20 lines of error log:<br><pre>";
    $lines = shell_exec("tail -20 " . escapeshellarg($error_log_path));
    echo $lines;
    echo "</pre>";
} else {
    echo "Error log file not found at: $error_log_path<br>";
    
    // Check common locations
    $common_locations = [
        '/var/log/apache2/error.log',
        '/var/log/httpd/error_log',
        '/var/log/nginx/error.log',
        '/var/log/php/error.log',
        'error.log'
    ];
    
    foreach ($common_locations as $location) {
        if (file_exists($location)) {
            echo "Found at: $location<br>";
            echo "Last 20 lines:<br><pre>";
            $lines = shell_exec("tail -20 " . escapeshellarg($location));
            echo $lines;
            echo "</pre>";
            break;
        }
    }
}
?>