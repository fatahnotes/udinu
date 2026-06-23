<?php
require_once __DIR__ . '/../../config/config.php';

/**
 * Fungsi untuk registrasi user
 */
function register_user($full_name, $email, $password, $confirm_password) {
    if (!check_rate_limit('register', 3, 600)) {
        return ['success' => false, 'message' => 'Terlalu banyak percobaan registrasi. Coba lagi nanti.'];
    }
    
    // Validasi input
    $errors = [];
    
    // Validasi nama
    $full_name = trim($full_name);
    if (empty($full_name) || strlen($full_name) < 3) {
        $errors[] = 'Nama lengkap minimal 3 karakter.';
    }
    
    if (strlen($full_name) > 255) {
        $errors[] = 'Nama lengkap maksimal 255 karakter.';
    }
    
    // Validasi email
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    
    if (strlen($email) > 255) {
        $errors[] = 'Email maksimal 255 karakter.';
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain.';
    }
    
    // Validasi password
    if ($password !== $confirm_password) {
        $errors[] = 'Password dan konfirmasi password tidak sama.';
    }
    
    $password_errors = validate_password_strength($password);
    if (!empty($password_errors)) {
        $errors = array_merge($errors, $password_errors);
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }
    
    $db = get_db_connection();
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user dengan password langsung
        $stmt = $db->prepare("
            INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active, created_at, updated_at)
            VALUES (?, ?, ?, TRUE, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            RETURNING id, uuid
        ");
        
        $stmt->execute([$email, $password_hash, $full_name]);
        $result = $stmt->fetch();
        $user_id = $result['id'];
        
        // Assign USER role
        $stmt = $db->prepare("
            INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at)
            SELECT ?, id, ?, CURRENT_TIMESTAMP
            FROM roles 
            WHERE role_code = 'USER'
        ");
        $stmt->execute([$user_id, $user_id]);
        
        // Buat profil kosong
        $stmt = $db->prepare("
            INSERT INTO profiles (user_id, is_profile_complete, created_at, updated_at)
            VALUES (?, FALSE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$user_id]);
        
        // Kirim email selamat datang
        $email_body = "Halo {$full_name},\n\n";
        $email_body .= "Selamat datang di SISTEM PENDAFTARAN GURU GARUDA!\n\n";
        $email_body .= "Akun Anda telah berhasil dibuat.\n";
        $email_body .= "Email: {$email}\n\n";
        $email_body .= "Silakan login untuk melengkapi profil Anda dan mendaftar seleksi Guru Garuda.\n\n";
        $email_body .= "Salam,\nTim Guru Garuda";
        
        $stmt = $db->prepare("
            INSERT INTO email_queue (recipient_email, subject, body, template_name, status, created_at)
            VALUES (?, 'Selamat Datang di SISTEM PENDAFTARAN GURU GARUDA', ?, 'welcome_email', 'pending', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$email, $email_body]);
        
        $db->commit();
        
        log_activity('REGISTER_SUCCESS', "New user registered: {$email}", $user_id);
        
        return [
            'success' => true, 
            'message' => 'Registrasi berhasil! Akun Anda sudah aktif. Silakan login.',
            'user_id' => $user_id,
            'user_email' => $email
        ];
        
    } catch (PDOException $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'];
    }
}

/**
 * Fungsi untuk login user
 */
function login_user($email, $password, $remember = false) {
    if (!check_rate_limit('login', 5, 300)) {
        return ['success' => false, 'message' => 'Terlalu banyak percobaan login. Coba lagi nanti.'];
    }
    
    $db = get_db_connection();
    
    try {
        // Cari user dengan email
        $stmt = $db->prepare("
            SELECT u.*, r.role_code, r.role_name 
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE u.email = ? AND u.is_active = TRUE
            LIMIT 1
        ");
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            log_activity('LOGIN_FAILED', "Email not found: {$email}");
            return ['success' => false, 'message' => 'Email atau password salah.'];
        }
        
        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $lock_time = date('H:i', strtotime($user['locked_until']));
            return ['success' => false, 'message' => "Akun terkunci sampai pukul {$lock_time}."];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Increment login attempts
            $stmt = $db->prepare("
                UPDATE users 
                SET login_attempts = login_attempts + 1,
                    locked_until = CASE 
                        WHEN login_attempts >= 4 THEN CURRENT_TIMESTAMP + INTERVAL '30 minutes'
                        ELSE locked_until 
                    END
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            log_activity('LOGIN_FAILED', "Wrong password for user: {$user['id']}");
            return ['success' => false, 'message' => 'Email atau password salah.'];
        }
        
        // Check if email is verified
        if (!$user['is_email_verified']) {
            return ['success' => false, 'message' => 'Email belum diverifikasi. Silakan cek email Anda.'];
        }
        
        // Reset login attempts on successful login
        $stmt = $db->prepare("
            UPDATE users 
            SET login_attempts = 0, 
                locked_until = NULL,
                last_login_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        // Get all roles for user
        $stmt = $db->prepare("
            SELECT r.role_code 
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$user['id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Set session data
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_uuid'] = $user['uuid'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role_code'];
        $_SESSION['user_roles'] = $roles; // Store all roles
        $_SESSION['last_activity'] = time();
        
        // Check if profile is complete
        $stmt = $db->prepare("SELECT is_profile_complete FROM profiles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch();
        
        $_SESSION['profile_complete'] = $profile ? $profile['is_profile_complete'] : false;
        
        // Set remember me token if needed
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 30 * 24 * 3600; // 30 days
            
            $stmt = $db->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $user['id'],
                $token,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                date('Y-m-d H:i:s', $expires)
            ]);
            
            setcookie('remember_token', $token, [
                'expires' => $expires,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        log_activity('LOGIN_SUCCESS', "User logged in: {$user['id']}", $user['id']);
        
        return [
            'success' => true,
            'message' => 'Login berhasil!',
            'profile_complete' => $_SESSION['profile_complete']
        ];
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.'];
    }
}

/**
 * Fungsi untuk logout
 */
function logout_user() {
    if (isset($_COOKIE['remember_token'])) {
        try {
            $db = get_db_connection();
            $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
        } catch (PDOException $e) {
            error_log("Logout remember token error: " . $e->getMessage());
        }
        // Secure cookie deletion with SameSite and HttpOnly
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    $user_id = $_SESSION['user_id'] ?? null;
    $user_name = $_SESSION['user_name'] ?? null;
    
    session_unset();
    session_destroy();
    
    if ($user_id) {
        log_activity('LOGOUT', "User logged out: {$user_name}", $user_id);
    }
    
    return true;
}

/**
 * Fungsi untuk cek apakah user sudah login
 */
function require_login($redirect_to = 'modules/auth/login.php') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = strtok($_SERVER['REQUEST_URI'], '?'); // Store path only, no query params
        header("Location: " . base_url($redirect_to));
        exit();
    }
    
    // Check session timeout
    if (!check_session_timeout()) {
        session_unset();
        session_destroy();
        $_SESSION['error'] = 'Session Anda telah berakhir. Silakan login kembali.';
        header("Location: " . base_url($redirect_to));
        exit();
    }
    
    return true;
}

/**
 * Fungsi untuk cek role user
 */
function require_role($required_roles, $redirect_to = 'modules/dashboard/dashboard.php') {
    require_login();
    
    if (!is_array($required_roles)) {
        $required_roles = [$required_roles];
    }
    
    $user_roles = $_SESSION['user_roles'] ?? [];
    
    // Check if user has any of the required roles
    $has_role = false;
    foreach ($required_roles as $role) {
        if (in_array($role, $user_roles)) {
            $has_role = true;
            break;
        }
    }
    
    if (!$has_role) {
        $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini.';
        header("Location: " . base_url($redirect_to));
        exit();
    }
    
    return true;
}

/**
 * Fungsi untuk check remember me token
 */
function check_remember_token() {
    if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
        $db = get_db_connection();
        
        try {
            $stmt = $db->prepare("
                SELECT us.*, u.*, r.role_code 
                FROM user_sessions us
                JOIN users u ON us.user_id = u.id
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE us.session_token = ? 
                AND us.expires_at > CURRENT_TIMESTAMP
                AND u.is_active = TRUE
                LIMIT 1
            ");
            
            $stmt->execute([$_COOKIE['remember_token']]);
            $user = $stmt->fetch();
            
            if ($user) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_uuid'] = $user['uuid'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role_code'];
                $_SESSION['last_activity'] = time();
                
                // Update session expiration
                $expires = time() + 30 * 24 * 3600;
                setcookie('remember_token', $_COOKIE['remember_token'], [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
                
                log_activity('REMEMBER_LOGIN', "Auto login via remember token", $user['user_id']);
                return true;
            }
        } catch (PDOException $e) {
            error_log("Remember token error: " . $e->getMessage());
        }
    }
    return false;
}

// functions-auth.php - Tambahkan fungsi ini

/**
 * Middleware untuk memeriksa kelengkapan profil
 */
function require_complete_profile() {
    if (!isset($_SESSION['profile_complete']) || $_SESSION['profile_complete'] !== true) {
        // Daftar halaman yang diizinkan meskipun profil belum lengkap
        $allowed_pages = [
            '/modules/profile/profile.php',
            '/modules/auth/logout.php',
            '/modules/auth/session-refresh.php'
        ];
        
        $current_page = $_SERVER['PHP_SELF'];
        
        // Jika bukan halaman yang diizinkan, redirect ke profile
        if (!in_array($current_page, $allowed_pages) && !str_contains($current_page, '?action=')) {
            $_SESSION['error_message'] = 'Silakan lengkapi profil Anda terlebih dahulu';
            header('Location: ' . base_url('modules/profile/profile.php'));
            exit;
        }
    }
}

/**
 * Update status profil di session setelah login
 */
function update_profile_session_status($user_id) {
    $db = get_db_connection();
    
    try {
        $stmt = $db->prepare("
            SELECT 
                is_profile_complete,
                CASE 
                    WHEN gender IS NOT NULL AND phone IS NOT NULL AND address IS NOT NULL 
                         AND birth_year IS NOT NULL AND last_education IS NOT NULL 
                         AND major IS NOT NULL AND institution IS NOT NULL 
                         AND id_provinsi IS NOT NULL AND id_kabupaten IS NOT NULL 
                         AND id_kecamatan IS NOT NULL 
                    THEN 1 
                    ELSE 0 
                END as all_required_filled
            FROM profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            $_SESSION['profile_complete'] = ($result['is_profile_complete'] == 1 && $result['all_required_filled'] == 1);
        } else {
            $_SESSION['profile_complete'] = false;
        }
    } catch (PDOException $e) {
        $_SESSION['profile_complete'] = false;
    }
}

?>