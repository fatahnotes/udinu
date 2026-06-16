<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/functions-auth.php';

// Logout user
logout_user();

// Redirect to home page
$_SESSION['success'] = 'Anda telah berhasil logout.';
header("Location: " . base_url());
exit();
?>