<?php
// ============================================
// CONFIG.PHP - GURU GARUDA SYSTEM
// ============================================

// Setup session configuration BEFORE starting session
$session_config = [
    'name' => 'UdinuSession',
    'cookie_lifetime' => 86400, // 24 jam
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'use_trans_sid' => false,
    'cookie_path' => '/',
    'cookie_domain' => '',
    'sid_length' => 128,
    'sid_bits_per_character' => 6
];

// Apply session configuration
foreach ($session_config as $key => $value) {
    ini_set('session.' . $key, (string)$value);
}

// Now start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Regenerate session ID untuk keamanan tambahan
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// ============================================
// ERROR REPORTING SETTINGS
// ============================================

// Deteksi environment
$isDevelopment = (getenv('APP_ENV') === 'development');

if ($isDevelopment) {
    // Development mode - tampilkan semua error kecuali deprecated
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    // Production mode - sembunyikan error dari user
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
}

// ============================================
// LOAD ENVIRONMENT VARIABLES
// ============================================

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse key=value
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remove quotes if present
            if (($value[0] === '"' && substr($value, -1) === '"') || 
                ($value[0] === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// ============================================
// DEFINE CONSTANTS
// ============================================

// Database Constants
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'ujian_dinas');
define('DB_USER', $_ENV['DB_USER'] ?? 'postgres');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
if (empty(DB_PASS)) {
    if (php_sapi_name() === 'cli') {
        die('ERROR: DB_PASS tidak ditemukan di .env file. Jalankan: cp .env.example .env dan isi kredensial.');
    }
    http_response_code(503);
    die('<h3>Sistem dalam pemeliharaan</h3>');
}

// Application Constants
define('BASE_URL', $_ENV['BASE_URL'] ?? '/udinu');
define('APP_NAME', $_ENV['APP_NAME'] ?? 'SISTEM PENDAFTARAN UJIAN DINAS');
define('SITE_NAME', 'Guru Garuda');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@udinu.id');
define('SUPPORT_EMAIL', $_ENV['SUPPORT_EMAIL'] ?? 'support@udinu.id');

// File Upload Constants
define('UPLOAD_MAX_SIZE', intval($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880)); // 5MB
$allowed_file_types = $_ENV['ALLOWED_FILE_TYPES'] ?? 'pdf,jpg,jpeg,png';
define('ALLOWED_FILE_TYPES', explode(',', $allowed_file_types));

// Security Constants
define('CSRF_TOKEN_LIFETIME', intval($_ENV['CSRF_TOKEN_LIFETIME'] ?? 1800)); // 30 menit
define('SESSION_TIMEOUT', intval($_ENV['SESSION_TIMEOUT'] ?? 3600)); // 1 jam
define('MAX_LOGIN_ATTEMPTS', intval($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5));

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/storage/uploads');
define('LOG_PATH', ROOT_PATH . '/storage/logs');
define('TEMP_PATH', ROOT_PATH . '/storage/temp');

// Timezone
$timezone = $_ENV['TIMEZONE'] ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Generate full URL
 */
function base_url($path = '') {
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base . ($path ? '/' . $path : '');
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token']) || 
        (isset($_SESSION['csrf_token_time']) && 
         time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME)) {
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validate_csrf_token($token) {
    if (empty($token)) {
        error_log("CSRF FAIL: Token is empty in POST");
        return false;
    }
    if (empty($_SESSION['csrf_token'])) {
        error_log("CSRF FAIL: No token in session. Session ID: " . session_id());
        // Auto-generate a new token so user can retry after refresh
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        return false;
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        error_log("CSRF FAIL: Token mismatch. SessLen=" . strlen($_SESSION['csrf_token']) . " PostLen=" . strlen($token));
        return false;
    }
    
    if (isset($_SESSION['csrf_token_time'])) {
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
            error_log("CSRF FAIL: Token expired. Age=" . (time() - $_SESSION['csrf_token_time']) . "s Limit=" . CSRF_TOKEN_LIFETIME . "s");
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
            return false;
        }
    }
    
    return true;
}

/**
 * Get Database Connection (Singleton Pattern)
 */
function get_db_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ]);
            
            // Set connection encoding
            $pdo->exec("SET NAMES 'UTF8'");
            $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
            
        } catch (PDOException $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Database connection failed: " . $e->getMessage());
            
            // Show user-friendly message — NEVER expose DB details even in dev
            error_log("DB Connection Error: " . $e->getMessage());
            http_response_code(503);
            die("<h3>Sistem sedang dalam pemeliharaan</h3><p>Silakan coba lagi beberapa saat.</p>");
        }
    }
    
    return $pdo;
}

/**
 * Sanitize Input Data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    if ($data === null) {
        return null;
    }
    
    $data = trim($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}

/**
 * Sanitize Output Data
 */
function sanitize_output($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Redirect Helper
 */
function redirect($url, $statusCode = 302) {
    header("Location: " . base_url($url), true, $statusCode);
    exit();
}

/**
 * Check if User is Logged In
 */
function is_logged_in() {
    return isset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['last_activity']);
}

/**
 * Check Session Timeout
 */
function check_session_timeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            // Session expired
            session_unset();
            session_destroy();
            
            // Start new session for flash message
            session_start();
            $_SESSION['session_expired'] = true;
            
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Log Activity to Database
 */
function log_activity($action, $details = null, $user_id = null) {
    try {
        $db = get_db_connection();
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (:user_id, :action, :details, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            ':user_id' => $user_id ?? ($_SESSION['user_id'] ?? null),
            ':action' => $action,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'
        ]);
        
        return true;
    } catch (Exception $e) {
        // Fallback to file logging if database fails
        $logMessage = sprintf(
            "[%s] ACTION: %s | USER: %s | DETAILS: %s | IP: %s\n",
            date('Y-m-d H:i:s'),
            $action,
            $user_id ?? ($_SESSION['user_id'] ?? 'GUEST'),
            $details ?? 'N/A',
            $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        );
        
        error_log($logMessage, 3, LOG_PATH . '/activity.log');
        return false;
    }
}

/**
 * Validate Uploaded File
 */
function validate_uploaded_file($file) {
    $errors = [];
    
    // Check if file was uploaded successfully
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi ukuran maksimal server.',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi ukuran maksimal form.',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP.'
        ];
        
        $errors[] = $uploadErrors[$file['error']] ?? 'Terjadi kesalahan saat mengunggah file.';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        $maxSizeMB = UPLOAD_MAX_SIZE / (1024 * 1024);
        $errors[] = "Ukuran file terlalu besar. Maksimal {$maxSizeMB}MB.";
    }
    
    // Check file type by extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_FILE_TYPES)) {
        $allowedTypes = implode(', ', ALLOWED_FILE_TYPES);
        $errors[] = "Format file tidak diizinkan. Hanya: {$allowedTypes}.";
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    
    if (!in_array($mime_type, $allowed_mimes)) {
        $errors[] = "Tipe file tidak valid.";
    }
    
    // Additional security check: validate image dimensions if it's an image
    if (in_array($mime_type, ['image/jpeg', 'image/png'])) {
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $errors[] = "File gambar tidak valid.";
        }
    }
    
    return $errors;
}

/**
 * Generate Secure Filename
 */
function generate_filename($original_name, $user_id = null) {
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(16));
    
    if ($user_id) {
        return "user_{$user_id}_{$timestamp}_{$random}.{$ext}";
    }
    
    return "file_{$timestamp}_{$random}.{$ext}";
}

/**
 * Rate Limiting
 */
function check_rate_limit($action, $limit = 5, $window = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'first_time' => time(),
            'locked_until' => 0
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Check if locked
    if (time() < $data['locked_until']) {
        return false;
    }
    
    // Reset counter if window has passed
    if (time() - $data['first_time'] > $window) {
        $_SESSION[$key] = [
            'count' => 1,
            'first_time' => time(),
            'locked_until' => 0
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $limit) {
        // Lock for 15 minutes
        $_SESSION[$key]['locked_until'] = time() + 900;
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Password Strength Validation
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter.";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 huruf besar.";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 huruf kecil.";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 angka.";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password harus mengandung minimal 1 simbol.";
    }
    
    return $errors;
}

/**
 * Check Email Validity
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if Email Exists in Database
 */
function email_exists($email) {
    try {
        $db = get_db_connection();
        // PERBAIKAN: Kolom yang benar adalah 'email' bukan 'user_email'
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND (is_deleted = FALSE OR is_deleted IS NULL)");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Email check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get User Role Name
 */
function get_role_name($role_code) {
    $roles = [
        'USER' => 'Pendaftar',
        'ADMIN_PUSAT' => 'Admin Pusat',
        'ASSESSOR' => 'Asesor',
        'SUPERADMIN' => 'Super Admin'
    ];
    
    return $roles[$role_code] ?? $role_code;
}

/**
 * Format Date for Display
 */
function format_date($date_string, $format = 'd/m/Y H:i') {
    if (empty($date_string)) return '-';
    
    $timestamp = strtotime($date_string);
    if ($timestamp === false) return $date_string;
    
    return date($format, $timestamp);
}

/**
 * Generate Random String
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * Truncate Text with Ellipsis
 */
function truncate_text($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $truncated = substr($text, 0, $length);
    $lastSpace = strrpos($truncated, ' ');
    
    if ($lastSpace !== false) {
        $truncated = substr($truncated, 0, $lastSpace);
    }
    
    return $truncated . '...';
}

/**
 * Get Current URL
 */
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    return $protocol . '://' . $host . $uri;
}

/**
 * Check if Request is AJAX
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * JSON Response Helper
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Check if File Exists and is Readable
 */
function file_exists_safe($path) {
    $full_path = realpath($path);
    if ($full_path === false) {
        return false;
    }
    
    // Prevent directory traversal
    if (strpos($full_path, ROOT_PATH) !== 0) {
        return false;
    }
    
    return file_exists($full_path) && is_readable($full_path);
}

/**
 * Create Directory if Not Exists
 */
function create_directory($path) {
    if (!file_exists($path)) {
        return mkdir($path, 0755, true);
    }
    return true;
}

function is_directory_writable($path) {
    return is_writable($path);
}

function get_max_upload_size() {
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    return min($max_upload, $max_post, $memory_limit) * 1024 * 1024;
}

/**
 * Initialize Required Directories
 */
function init_directories() {
    $directories = [
        UPLOAD_PATH,
        LOG_PATH,
        TEMP_PATH,
        UPLOAD_PATH . '/documents',
        UPLOAD_PATH . '/profiles',
        UPLOAD_PATH . '/temp'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Add .htaccess protection to upload directories
        if (strpos($dir, 'uploads') !== false) {
            $htaccess = $dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n");
            }
        }
    }
}

function optimize_upload_settings() {
    // Increase upload limits
    ini_set('upload_max_filesize', '10M');
    ini_set('post_max_size', '12M');
    ini_set('max_execution_time', '300');
    ini_set('max_input_time', '300');
    ini_set('memory_limit', '256M');
    
    // Optimize for multiple file uploads
    ini_set('max_file_uploads', '20');
    
    // Disable output buffering for faster uploads
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

// Call optimization function
optimize_upload_settings();

/**
 * Generate unique filename for PDF
 */
function generate_pdf_filename($prefix, $original_name) {
    $timestamp = time();
    $random = bin2hex(random_bytes(4));
    $safe_name = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
    return $prefix . '_' . $timestamp . '_' . $random . '_' . $safe_name . '.pdf';
}

/**
 * Validate PDF file
 */
function validate_pdf_file($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    // Check if file is actually a PDF
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    
    return $mime_type === 'application/pdf';
}

/**
 * Enhanced error logging for file operations
 */
function log_file_operation($message, $level = 'INFO') {
    $log_file = dirname(__DIR__, 2) . '/storage/logs/file_operations.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Create logs directory if not exists
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
    error_log($message);
}

/**
 * Debug database operations
 */
function debug_database($query, $params = []) {
    $log_file = dirname(__DIR__, 2) . '/storage/logs/database.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [DEBUG] Query: $query" . PHP_EOL;
    
    if (!empty($params)) {
        $log_message .= "[$timestamp] [DEBUG] Params: " . print_r($params, true) . PHP_EOL;
    }
    
    // Create logs directory if not exists
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// ============================================
// INITIALIZATION
// ============================================

// Create required directories
init_directories();

// Check session timeout
if (is_logged_in()) {
    check_session_timeout();
}

// Auto-generate CSRF token for forms
if (!isset($_SESSION['csrf_token'])) {
    generate_csrf_token();
}

// ============================================
// SECURITY HEADERS
// ============================================

if (!headers_sent()) {
    // Security headers
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // HSTS — only when HTTPS is active
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // CORS — explicit whitelist, NOT substring matching
    $allowed_origins = [];
    if ($isDevelopment) {
        $allowed_origins = ['http://localhost', 'http://127.0.0.1', 'http://localhost:8080'];
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin && in_array($origin, $allowed_origins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    
    // CSP — always active, use Report-Only in dev to detect issues
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; "
         . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://use.fontawesome.com; "
         . "img-src 'self' data: https:; "
         . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://use.fontawesome.com; "
         . "connect-src 'self'; "
         . "frame-ancestors 'none';";
    if ($isDevelopment) {
        header('Content-Security-Policy-Report-Only: ' . $csp);
    } else {
        header('Content-Security-Policy: ' . $csp);
    }
}
?>