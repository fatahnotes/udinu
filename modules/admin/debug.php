<?php
// modules/admin/debug.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

echo "<h2>Debug User Management System</h2>";
echo "<pre>";

// Cek session
echo "=== SESSION DATA ===\n";
print_r($_SESSION);
echo "\n";

// Cek apakah user login
echo "=== LOGIN STATUS ===\n";
echo "is_logged_in(): " . (is_logged_in() ? 'TRUE' : 'FALSE') . "\n";
echo "User Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";

// Cek koneksi database
echo "\n=== DATABASE CONNECTION ===\n";
try {
    $db = get_db_connection();
    echo "Database connection: SUCCESS\n";
    
    // Cek tabel users
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Total users in database: " . $count . "\n";
    
    // Cek tabel roles
    $stmt = $db->query("SELECT COUNT(*) FROM roles");
    $count = $stmt->fetchColumn();
    echo "Total roles in database: " . $count . "\n";
    
    // Cek tabel user_roles
    $stmt = $db->query("SELECT COUNT(*) FROM user_roles");
    $count = $stmt->fetchColumn();
    echo "Total user_roles in database: " . $count . "\n";
    
    // Cek struktur tabel users
    echo "\n=== USERS TABLE STRUCTURE ===\n";
    $stmt = $db->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name = 'users' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo $col['column_name'] . " | " . $col['data_type'] . " | " . $col['is_nullable'] . "\n";
    }
    
    // Cek 5 user pertama
    echo "\n=== SAMPLE USERS (first 5) ===\n";
    $stmt = $db->query("SELECT id, full_name, email, is_active, is_deleted FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    print_r($users);
    
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
}

// Test API endpoint langsung
echo "\n=== TEST API DIRECTLY ===\n";
$api_url = base_url('modules/admin/user-api.php?action=stats');
echo "API URL: " . $api_url . "\n";

// Coba akses API via file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . session_name() . "=" . session_id() . "\r\n"
    ]
]);

$api_response = @file_get_contents($api_url, false, $context);
if ($api_response) {
    echo "API Response: " . $api_response . "\n";
} else {
    echo "API Response: FAILED TO FETCH\n";
}

echo "</pre>";
?>