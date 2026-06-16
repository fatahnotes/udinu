<?php
// modules/admin/user-api.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

// Hanya SUPERADMIN yang bisa mengakses
require_login();
if ($_SESSION['user_role'] !== 'SUPERADMIN') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

header('Content-Type: application/json');
$db = get_db_connection();
$action = $_GET['action'] ?? '';

// Cegah output sebelum JSON
ob_clean();

try {
    switch ($action) {
        case 'stats':
            get_user_stats($db);
            break;
        case 'list':
            get_user_list($db);
            break;
        case 'create':
            create_user($db);
            break;
        case 'update':
            update_user($db);
            break;
        case 'delete':
            delete_user($db);
            break;
        case 'reset-password':
            reset_password($db);
            break;
        case 'verify-email':
            verify_user_email($db);
        break;
        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    }
} catch (Exception $e) {
    error_log("User API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}

function get_user_stats($db) {
    $stats = [];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE (is_deleted = FALSE OR is_deleted IS NULL)");
    $stats['total_users'] = (int)$stmt->fetchColumn();
    
    // Total active users
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_active = TRUE AND (is_deleted = FALSE OR is_deleted IS NULL)");
    $stats['total_active'] = (int)$stmt->fetchColumn();
    
    // Total inactive users
    $stats['total_inactive'] = $stats['total_users'] - $stats['total_active'];
    
    // Hitung berdasarkan role
    $roles = ['USER', 'ADMIN_VERIFIKATOR', 'ASSESSOR', 'SUPERADMIN'];
    foreach ($roles as $role) {
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT u.id)
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE (u.is_deleted = FALSE OR u.is_deleted IS NULL)
            AND (r.role_code = ? OR (? = 'USER' AND r.role_code IS NULL))
        ");
        $stmt->execute([$role, $role]);
        $count = (int)$stmt->fetchColumn();
        
        if ($role === 'USER') {
            $stats['total_pendaftar'] = $count;
        } elseif ($role === 'ADMIN_VERIFIKATOR' || $role === 'SUPERADMIN') {
            $stats['total_admin'] = isset($stats['total_admin']) ? $stats['total_admin'] + $count : $count;
        } elseif ($role === 'ASSESSOR') {
            $stats['total_asesor'] = $count;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function get_user_list($db) {
    try {
        $page = intval($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $where = ["(u.is_deleted = FALSE OR u.is_deleted IS NULL)"];
        $params = [];
        
        // Filter by role
        if (!empty($_GET['role'])) {
            $where[] = "r.role_code = ?";
            $params[] = $_GET['role'];
        }
        
        // Filter by status
        if (!empty($_GET['status'])) {
            $where[] = "u.is_active = ?";
            $params[] = ($_GET['status'] === 'active') ? 1 : 0;
        }
        
        // Filter by email verification status
        if (!empty($_GET['email_verified'])) {
            if ($_GET['email_verified'] === 'verified') {
                $where[] = "u.is_email_verified = TRUE";
            } elseif ($_GET['email_verified'] === 'not_verified') {
                $where[] = "(u.is_email_verified = FALSE OR u.is_email_verified IS NULL)";
            }
        }
        
        // Search
        if (!empty($_GET['search'])) {
            $where[] = "(u.full_name ILIKE ? OR u.email ILIKE ?)";
            $searchTerm = '%' . $_GET['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $countSql = "
            SELECT COUNT(DISTINCT u.id) 
            FROM users u 
            LEFT JOIN user_roles ur ON u.id = ur.user_id 
            LEFT JOIN roles r ON ur.role_id = r.id 
            {$whereClause}
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        // Get users dengan email verification status
        $sql = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.is_active,
                u.is_email_verified,
                u.created_at,
                COALESCE(r.role_code, 'USER') as role
            FROM users u 
            LEFT JOIN user_roles ur ON u.id = ur.user_id 
            LEFT JOIN roles r ON ur.role_id = r.id 
            {$whereClause}
            GROUP BY u.id, u.full_name, u.email, u.is_active, u.is_email_verified, u.created_at, r.role_code
            ORDER BY u.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dates
        foreach ($users as &$user) {
            if ($user['created_at']) {
                try {
                    $date = new DateTime($user['created_at']);
                    $user['created_at_formatted'] = $date->format('d/m/Y H:i');
                } catch (Exception $e) {
                    $user['created_at_formatted'] = $user['created_at'];
                }
            } else {
                $user['created_at_formatted'] = '-';
            }
        }
        
        $pagination = [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'per_page' => $limit
        ];
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'users' => $users,
                'pagination' => $pagination
            ]
        ]);
    } catch (Exception $e) {
        error_log("List users error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error mengambil data user: ' . $e->getMessage()]);
    }
}

function create_user($db) {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $role = sanitize_input($_POST['role'] ?? 'USER');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Nama, email, dan password harus diisi']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
        return;
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 8 karakter']);
        return;
    }
    
    $valid_roles = ['USER', 'ADMIN_VERIFIKATOR', 'ASSESSOR', 'SUPERADMIN'];
    if (!in_array($role, $valid_roles)) {
        echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
        return;
    }
    
    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar']);
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Insert user
        $stmt = $db->prepare("
            INSERT INTO users (full_name, email, password_hash, is_active, is_email_verified)
            VALUES (?, ?, ?, TRUE, FALSE)
            RETURNING id
        ");
        
        $stmt->execute([$name, $email, $hashed_password]);
        $user_id = $stmt->fetchColumn();
        
        // Get role_id
        $stmt = $db->prepare("SELECT id FROM roles WHERE role_code = ?");
        $stmt->execute([$role]);
        $role_data = $stmt->fetch();
        
        if ($role_data) {
            $role_id = $role_data['id'];
            
            // Assign role
            $stmt = $db->prepare("
                INSERT INTO user_roles (user_id, role_id, assigned_by)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$user_id, $role_id, $_SESSION['user_id'] ?? null]);
        }
        
        $db->commit();
        
        // Log activity
        if (function_exists('log_activity')) {
            log_activity('USER_CREATE', "Created user: {$email} with role: {$role}", $_SESSION['user_id'] ?? null);
        }
        
        echo json_encode(['success' => true, 'message' => 'User berhasil dibuat']);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Create user error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal membuat user: ' . $e->getMessage()]);
    }
}

function update_user($db) {
    try {
        $id = intval($_POST['id'] ?? 0);
        $name = sanitize_input($_POST['name'] ?? '');
        $role_name = sanitize_input($_POST['role'] ?? 'USER');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_email_verified = isset($_POST['is_email_verified']) ? 1 : 0;

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            return;
        }

        // Validasi role (opsional, sesuai kebutuhan aplikasi)
        $valid_roles = ['USER', 'ADMIN_VERIFIKATOR', 'ASSESSOR', 'SUPERADMIN'];
        if (!in_array($role_name, $valid_roles, true)) {
            echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
            return;
        }

        // Prevent self-deactivation
        if ($id == ($_SESSION['user_id'] ?? 0) && !$is_active) {
            echo json_encode(['success' => false, 'message' => 'Tidak bisa menonaktifkan diri sendiri']);
            return;
        }

        $db->beginTransaction();

        try {
            // Update user info di tabel users
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = ?, is_active = ?, is_email_verified = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$name, $is_active, $is_email_verified, $id]);

            // Get role_id dari tabel roles berdasarkan role_code
            $stmt = $db->prepare("SELECT id FROM roles WHERE role_code = ?");
            $stmt->execute([$role_name]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                throw new Exception("Role tidak ditemukan di database");
            }

            $role_id = $role['id'];

            // Cek apakah user sudah punya role
            $stmt = $db->prepare("SELECT 1 FROM user_roles WHERE user_id = ?");
            $stmt->execute([$id]);

            if ($stmt->fetch()) {
                // Update existing role
                $stmt = $db->prepare("
                    UPDATE user_roles 
                    SET role_id = ?, assigned_by = ?, assigned_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");
                $stmt->execute([$role_id, $_SESSION['user_id'] ?? null, $id]);
            } else {
                // Insert new role
                $stmt = $db->prepare("
                    INSERT INTO user_roles (user_id, role_id, assigned_by)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$id, $role_id, $_SESSION['user_id'] ?? null]);
            }

            $db->commit();

            // Log activity
            if (function_exists('log_activity')) {
                $verificationStatus = $is_email_verified ? 'VERIFIED' : 'UNVERIFIED';
                log_activity(
                    'USER_UPDATE',
                    "Memperbarui user ID: {$id} (Email: {$verificationStatus})",
                    $_SESSION['user_id'] ?? null
                );
            }

            echo json_encode(['success' => true, 'message' => 'User berhasil diperbarui']);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Update user transaction error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui user: ' . $e->getMessage()]);
        }
    } catch (Exception $e) {
        error_log("Update user error: " . $e->getMessage());
        // INI YANG TADI SALAH: ['success' false, ...]
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
}


function delete_user($db) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        return;
    }
    
    if ($id == ($_SESSION['user_id'] ?? 0)) {
        echo json_encode(['success' => false, 'message' => 'Tidak bisa menghapus diri sendiri']);
        return;
    }
    
    $stmt = $db->prepare("
        UPDATE users 
        SET is_active = FALSE, is_deleted = TRUE, deleted_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->execute([$id]);
    
    if (function_exists('log_activity')) {
        log_activity('USER_DELETE', "Deleted user ID: {$id}", $_SESSION['user_id'] ?? null);
    }
    
    echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
}

function reset_password($db) {
    $id = intval($_POST['id'] ?? 0);
    $default_password = 'Sos111';
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $id]);
    
    if (function_exists('log_activity')) {
        log_activity('PASSWORD_RESET', "Reset password for user ID: {$id}", $_SESSION['user_id'] ?? null);
    }
    
    echo json_encode(['success' => true, 'message' => 'Password berhasil direset ke default']);
}

function verify_user_email($db) {
    try {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            return;
        }
        
        // Cegah self-verification untuk admin? Mungkin tidak perlu
        // Tapi bisa ditambahkan jika diperlukan
        
        $stmt = $db->prepare("
            UPDATE users 
            SET is_email_verified = TRUE, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([$id]);
        
        // Log activity
        if (function_exists('log_activity')) {
            log_activity('EMAIL_VERIFY_MANUAL', "Email verified manually for user ID: {$id}", $_SESSION['user_id'] ?? null);
        }
        
        echo json_encode(['success' => true, 'message' => 'Email berhasil diverifikasi']);
    } catch (Exception $e) {
        error_log("Verify email error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal memverifikasi email: ' . $e->getMessage()]);
    }
}
?>