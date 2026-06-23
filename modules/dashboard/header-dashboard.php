<?php
require_once __DIR__ . '/../../config/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . base_url('modules/auth/login.php'));
    exit;
}

// ============================================
// DYNAMIC MENU SYSTEM — Load from database
// ============================================
function get_sidebar_menu($user_role) {
    $db = get_db_connection();
    
    // Cek apakah tabel menus ada
    try {
        $db->query("SELECT 1 FROM menus LIMIT 1");
    } catch (Exception $e) {
        // Fallback ke hardcoded menu jika tabel belum ada
        return get_fallback_menu($user_role);
    }
    
    // Ambil menu parent untuk role ini (atau ALL)
    $stmt = $db->prepare("
        SELECT * FROM menus 
        WHERE parent_id IS NULL 
        AND (role_code = ? OR role_code = 'ALL')
        AND is_active = TRUE 
        AND is_visible = TRUE
        ORDER BY display_order, id
    ");
    $stmt->execute([$user_role]);
    $parents = $stmt->fetchAll();
    
    if (empty($parents)) {
        return get_fallback_menu($user_role);
    }
    
    // Ambil semua child menu untuk role ini
    $stmt = $db->prepare("
        SELECT * FROM menus 
        WHERE parent_id IS NOT NULL 
        AND (role_code = ? OR role_code = 'ALL')
        AND is_active = TRUE 
        AND is_visible = TRUE
        ORDER BY display_order, id
    ");
    $stmt->execute([$user_role]);
    $all_children = $stmt->fetchAll();
    
    // Group children by parent_id
    $children_by_parent = [];
    foreach ($all_children as $child) {
        $children_by_parent[$child['parent_id']][] = $child;
    }
    
    // Build menu structure
    $menu = [];
    foreach ($parents as $parent) {
        $item = [
            'id' => $parent['id'],
            'icon' => $parent['icon'],
            'label' => $parent['label'],
            'url' => $parent['url'],
            'active' => $parent['active_key'],
            'children' => []
        ];
        
        if (isset($children_by_parent[$parent['id']])) {
            foreach ($children_by_parent[$parent['id']] as $child) {
                $item['children'][] = [
                    'id' => $child['id'],
                    'icon' => $child['icon'],
                    'label' => $child['label'],
                    'url' => $child['url'],
                    'active' => $child['active_key']
                ];
            }
        }
        
        $menu[] = $item;
    }
    
    return $menu;
}

/**
 * Fallback menu jika tabel menus belum ada
 */
function get_fallback_menu($user_role) {
    $menu = [];
    
    switch ($user_role) {
        case 'SUPERADMIN':
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard', 'children' => []],
                [
                    'icon' => 'file-alt', 'label' => 'Manajemen Ujian', 'url' => '#', 'active' => 'exam', 'children' => [
                        ['icon' => 'list', 'label' => 'Daftar Ujian', 'url' => '../admin/vacancy-management.php', 'active' => 'vacancy-management'],
                        ['icon' => 'database', 'label' => 'Master Data Ujian', 'url' => '../admin/exam-master.php', 'active' => 'exam-master'],
                        ['icon' => 'clipboard-list', 'label' => 'Rekap Pendaftar', 'url' => '../admin/exam-applicants.php', 'active' => 'exam-applicants'],
                    ]
                ],
                ['icon' => 'users', 'label' => 'Manajemen User', 'url' => '../admin/user-management.php', 'active' => 'user-management', 'children' => []],
                [
                    'icon' => 'check-circle', 'label' => 'Verifikasi & Seleksi', 'url' => '#', 'active' => 'verification', 'children' => [
                        ['icon' => 'check-double', 'label' => 'Verifikasi Berkas', 'url' => '../verification/verification.php', 'active' => 'verification'],
                        ['icon' => 'users', 'label' => 'Daftar Pendaftar', 'url' => '../verification/applicant-list.php', 'active' => 'applicant-list'],
                    ]
                ],
                [
                    'icon' => 'star', 'label' => 'Penilaian', 'url' => '#', 'active' => 'scoring', 'children' => [
                        ['icon' => 'pen', 'label' => 'Input Nilai', 'url' => '../scoring/scoring.php', 'active' => 'scoring'],
                        ['icon' => 'chart-bar', 'label' => 'Hasil Penilaian', 'url' => '../scoring/results.php', 'active' => 'scoring-results'],
                    ]
                ],
                [
                    'icon' => 'bullhorn', 'label' => 'Pengumuman', 'url' => '#', 'active' => 'announcement', 'children' => [
                        ['icon' => 'envelope', 'label' => 'Buat Pengumuman', 'url' => '../announcement/announcement.php', 'active' => 'announcement'],
                        ['icon' => 'certificate', 'label' => 'Sertifikat', 'url' => '../announcement/certificate.php', 'active' => 'certificate'],
                    ]
                ],
            ];
            break;
            
        case 'ADMIN_VERIFIKATOR':
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard', 'children' => []],
                [
                    'icon' => 'check-circle', 'label' => 'Verifikasi Berkas', 'url' => '#', 'active' => 'verification', 'children' => [
                        ['icon' => 'check-double', 'label' => 'Verifikasi Dokumen', 'url' => '../verification/verification.php', 'active' => 'verification'],
                        ['icon' => 'users', 'label' => 'Daftar Pendaftar', 'url' => '../verification/applicant-list.php', 'active' => 'applicant-list'],
                    ]
                ],
                [
                    'icon' => 'chart-bar', 'label' => 'Rekap & Laporan', 'url' => '#', 'active' => 'reports', 'children' => [
                        ['icon' => 'chart-pie', 'label' => 'Statistik', 'url' => '../verification/statistics.php', 'active' => 'statistics'],
                    ]
                ],
            ];
            break;
            
        case 'ASSESSOR':
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard', 'children' => []],
                [
                    'icon' => 'star', 'label' => 'Penilaian', 'url' => '#', 'active' => 'scoring', 'children' => [
                        ['icon' => 'pen', 'label' => 'Input Nilai', 'url' => '../scoring/scoring.php', 'active' => 'scoring'],
                        ['icon' => 'clipboard-check', 'label' => 'Hasil Penilaian', 'url' => '../scoring/results.php', 'active' => 'scoring-results'],
                    ]
                ],
            ];
            break;
            
        case 'USER':
        default:
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard', 'children' => []],
                [
                    'icon' => 'user', 'label' => 'Profil Saya', 'url' => '#', 'active' => 'profile', 'children' => [
                        ['icon' => 'user-edit', 'label' => 'Edit Profil', 'url' => '../profile/profile.php', 'active' => 'profile'],
                    ]
                ],
                [
                    'icon' => 'file-alt', 'label' => 'Pendaftaran Ujian', 'url' => '#', 'active' => 'submission', 'children' => [
                        ['icon' => 'plus-circle', 'label' => 'Daftar Ujian Baru', 'url' => '../submission/submission.php', 'active' => 'submission'],
                    ]
                ],
                [
                    'icon' => 'history', 'label' => 'Status & Hasil', 'url' => '#', 'active' => 'status', 'children' => [
                        ['icon' => 'tasks', 'label' => 'Status Proses', 'url' => '../submission/status.php', 'active' => 'status'],
                        ['icon' => 'bullhorn', 'label' => 'Pengumuman', 'url' => '../announcement/view.php', 'active' => 'announcement-view'],
                    ]
                ],
            ];
            break;
    }
    
    return $menu;
}

$sidebar_menu = get_sidebar_menu($_SESSION['user_role']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistem Pendaftaran UDIN & UPKP</title>
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:slnt,wght@-10..0,100..900&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo base_url('assets/css/style.css'); ?>" rel="stylesheet">
    
    <?php if (!empty($customCSS)) : ?>
    <style><?php echo $customCSS; ?></style>
    <?php endif; ?>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f4f7fc;
            overflow-x: hidden;
        }

        /* Dashboard Styles */
        .dashboard-wrapper {
            display: flex;
            align-items: stretch;
            min-height: 100vh;
            background-color: #f4f7fc;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            min-width: 260px;
            background: linear-gradient(180deg, #0a2463 0%, #123499 30%, #0d6efd 100%);
            color: white;
            padding: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden; /* HANYA container, jangan scroll */
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
        }

        /* Sidebar Logo Area */
        .sidebar-logo-wrap {
            padding: 20px 16px 10px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-logo {
            display: block;
            text-decoration: none;
        }

        .sidebar-logo img {
            max-width: 100%;
            height: auto;
            max-height: 55px;
            display: block;
            margin: 0 auto;
            transition: all 0.3s;
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .sidebar-logo:hover img {
            transform: scale(1.05);
            opacity: 1;
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-align: left;
            flex-shrink: 0;
        }

        .sidebar-header .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header .brand-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.12);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            line-height: 1.3;
        }

        .sidebar-header .sidebar-subtitle {
            color: rgba(255,255,255,0.5);
            font-size: 0.68rem;
            margin: 2px 0 0;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            padding: 8px 0;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0; /* Penting untuk flex child agar bisa scroll */
        }
        
        /* Custom scrollbar for sidebar-menu */
        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-menu::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
        }
        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.35);
        }

        .sidebar-menu .nav {
            padding: 0 8px;
        }

        .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 10px 14px;
            margin: 1px 0;
            border-radius: 8px;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            position: relative;
        }

        .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
            font-size: 0.85rem;
            opacity: 0.8;
            transition: all 0.25s ease;
        }

        .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.12);
            text-decoration: none;
        }
        
        .nav-link:hover i {
            opacity: 1;
        }

        .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.18);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .nav-link.active i {
            opacity: 1;
        }
        
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 3px;
            background: #fff;
            border-radius: 0 3px 3px 0;
        }

        .nav-link .badge {
            margin-left: auto;
            font-size: 0.7em;
        }

        .nav-divider {
            border-color: rgba(255,255,255,0.08);
            margin: 10px 18px;
        }
        
        /* ===== SUBMENU STYLES ===== */
        .has-submenu {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .has-submenu .submenu-arrow {
            font-size: 0.6rem;
            transition: transform 0.3s ease;
            opacity: 0.6;
        }
        .has-submenu[aria-expanded="true"] .submenu-arrow {
            transform: rotate(180deg);
            opacity: 1;
        }
        .has-submenu:hover .submenu-arrow {
            opacity: 1;
        }
        
        .submenu {
            margin-left: 16px;
            border-left: 1px solid rgba(255,255,255,0.1);
            padding-left: 0;
        }
        .submenu .nav-link {
            font-size: 0.8rem;
            padding: 8px 14px 8px 22px;
            color: rgba(255,255,255,0.6);
            font-weight: 400;
            border-radius: 0 8px 8px 0;
        }
        .submenu .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.08);
        }
        .submenu .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.15);
            font-weight: 600;
        }
        .submenu .nav-link i {
            font-size: 0.7rem;
            width: 18px;
            margin-right: 6px;
            opacity: 0.7;
        }
        .submenu .nav-link.active i {
            opacity: 1;
        }
        
        .collapse {
            transition: height 0.3s ease;
        }

        /* Sidebar Footer (Logout - sticky bottom) */
        .sidebar-footer {
            padding: 10px 12px;
            border-top: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
            background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.15) 100%);
        }
        
        .sidebar-footer .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 500;
            transition: all 0.25s ease;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        
        .sidebar-footer .logout-btn:hover {
            color: #fff;
            background: rgba(220, 53, 69, 0.25);
            text-decoration: none;
        }
        
        .sidebar-footer .logout-btn i {
            width: 20px;
            text-align: center;
            font-size: 0.85rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top Navbar */
        .top-navbar {
            background: #ffffff;
            padding: 0 30px;
            height: 64px;
            border-bottom: 1px solid #e5e9f0;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.97);
        }

        .top-navbar .navbar-brand {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.1rem;
            letter-spacing: -0.3px;
        }

        .top-navbar .sidebar-toggle-btn {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            display: none;
        }
        
        .top-navbar .sidebar-toggle-btn:hover {
            background: #e2e8f0;
            color: #1e293b;
        }
        
        @media (max-width: 991.98px) {
            .top-navbar .sidebar-toggle-btn {
                display: inline-flex;
            }
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 10px;
            transition: all 0.2s;
        }
        
        .user-dropdown:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .user-info {
            margin-left: 10px;
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .user-role {
            color: #6c757d;
            font-size: 12px;
        }

        /* Content Area */
        .content-area {
            padding: 24px 30px;
            flex: 1;
        }

        /* Card Styles */
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background: none;
            border: none;
            padding: 0 0 20px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h4 {
            color: #0d6efd;
            font-weight: 600;
            margin: 0;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            color: #6c757d;
        }

        /* Button Styles */
        .btn-dashboard {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-dashboard:hover {
            background: linear-gradient(135deg, #0b5ed7 0%, #0bb8d9 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }

        .btn-outline-dashboard {
            background: white;
            border: 2px solid #0d6efd;
            color: #0d6efd;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-outline-dashboard:hover {
            background: #0d6efd;
            color: white;
            transform: translateY(-2px);
        }

        /* Alert Styles */
        .dashboard-alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        /* Progress Bar */
        .profile-progress {
            height: 20px;
            border-radius: 10px;
            background-color: #e9ecef;
            margin-bottom: 15px;
        }

        .profile-progress .progress-bar {
            border-radius: 10px;
            background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 10px 0;
            margin-top: 10px;
        }

        .dropdown-item {
            padding: 10px 20px;
            color: #495057;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }

        .dropdown-divider {
            margin: 5px 0;
        }

        /* Toast Container */
        .toast-container {
            z-index: 1100;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: 270px;
                height: 100vh;
                height: 100dvh;
                transform: translateX(-100%);
                z-index: 1050;
                box-shadow: 4px 0 30px rgba(0,0,0,0.2);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1049;
                backdrop-filter: blur(2px);
            }
            
            .sidebar-overlay.active {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-navbar {
                padding: 0 16px;
                height: 60px;
            }
            
            .content-area {
                padding: 16px;
            }
            
            .user-info {
                display: none;
            }
            
            .top-navbar .navbar-brand {
                font-size: 0.95rem;
            }
        }

        /* Mobile toggle button */
        .sidebar-toggle {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            display: none;
            margin: 0 16px 6px;
            text-align: center;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.2);
        }

        @media (max-width: 991.98px) {
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>

<body>
    <!-- Spinner -->
    <div id="spinner" class="spinner-border text-primary position-fixed" style="width: 3rem; height: 3rem; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none;">
        <span class="visually-hidden">Loading...</span>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    

    <div class="dashboard-wrapper">

        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Logo OSDM Kemdiktisaintek -->
            <div class="sidebar-logo-wrap">
                <a href="<?php echo base_url('index.php'); ?>" class="sidebar-logo" aria-label="OSDM Kemdiktisaintek">
                    <img src="<?php echo base_url('img/logo-dikti.svg'); ?>" alt="Logo OSDM Kemdiktisaintek" onerror="this.onerror=null; this.src='<?php echo base_url('assets/img/logo-placeholder.png'); ?>'; this.alt='Logo Kemdiktisaintek';">
                </a>
            </div>
            <button class="sidebar-toggle" id="sidebarToggleMobile">
                <i class="fas fa-bars me-2"></i>Menu Navigasi
            </button>
            
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="brand-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <h3>UDIN &amp; UPKP</h3>
                        <p class="sidebar-subtitle">Dashboard <?php echo isset($_SESSION['user_role']) ? strtoupper($_SESSION['user_role']) : ''; ?></p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-menu">
                <ul class="nav flex-column">
                    <?php foreach ($sidebar_menu as $item): 
                        $hasChildren = !empty($item['children']);
                        $isActive = ($activePage == $item['active']);
                        // Cek apakah ada child yang active
                        if ($hasChildren) {
                            foreach ($item['children'] as $child) {
                                if ($activePage == $child['active']) { $isActive = true; break; }
                            }
                        }
                    ?>
                    <li class="nav-item">
                        <?php if ($hasChildren): ?>
                        <!-- Parent dengan submenu -->
                        <a href="#submenu-<?php echo $item['id'] ?? md5($item['label']); ?>" 
                           class="nav-link has-submenu <?php echo $isActive ? 'active' : ''; ?>"
                           data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $isActive ? 'true' : 'false'; ?>">
                            <i class="fas fa-<?php echo $item['icon']; ?>"></i> 
                            <span><?php echo $item['label']; ?></span>
                            <i class="fas fa-chevron-down submenu-arrow ms-auto"></i>
                        </a>
                        <div class="collapse <?php echo $isActive ? 'show' : ''; ?>" id="submenu-<?php echo $item['id'] ?? md5($item['label']); ?>">
                            <ul class="nav flex-column submenu">
                                <?php foreach ($item['children'] as $child): ?>
                                <li class="nav-item">
                                    <a href="<?php echo base_url('modules/dashboard/' . $child['url']); ?>" 
                                       class="nav-link <?php echo ($activePage == $child['active']) ? 'active' : ''; ?>">
                                        <i class="fas fa-<?php echo $child['icon']; ?>"></i> 
                                        <?php echo $child['label']; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php else: ?>
                        <!-- Menu tanpa submenu -->
                        <a href="<?php echo ($item['url'] === '#') ? '#' : base_url('modules/dashboard/' . $item['url']); ?>" 
                           class="nav-link <?php echo $isActive ? 'active' : ''; ?>">
                            <i class="fas fa-<?php echo $item['icon']; ?>"></i> 
                            <?php echo $item['label']; ?>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <hr class="nav-divider">
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-file-pdf"></i> Panduan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-question-circle"></i> Bantuan
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="<?php echo base_url('modules/auth/logout.php'); ?>" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle-btn" id="sidebarToggleDesktop" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="navbar-brand"><?php echo $pageTitle; ?></span>
                </div>
                
                <div class="user-dropdown dropdown">
                    <div class="d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php 
                            if (isset($_SESSION['user_name'])) {
                                echo strtoupper(substr($_SESSION['user_name'], 0, 1));
                            }
                            ?>
                        </div>
                        <div class="user-info d-none d-md-block">
                            <div class="user-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?></div>
                            <div class="user-role"><?php echo isset($_SESSION['user_role']) ? htmlspecialchars($_SESSION['user_role']) : ''; ?></div>
                        </div>
                    </div>
                    
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?php echo base_url('modules/profile/profile.php'); ?>">
                                <i class="fas fa-user me-2"></i>Profil Saya
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-cog me-2"></i>Pengaturan
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo base_url('modules/auth/logout.php'); ?>">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Content Area -->
            <div class="content-area">

<script>
// JavaScript untuk interaktivitas dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle untuk desktop dan mobile
    const sidebarToggleDesktop = document.getElementById('sidebarToggleDesktop');
    const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Fungsi untuk toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active');
        }
        // Prevent body scroll when sidebar is open on mobile
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }
    
    function closeSidebar() {
        sidebar.classList.remove('active');
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('active');
        }
        document.body.style.overflow = '';
    }
    
    // Event listener untuk desktop toggle
    if (sidebarToggleDesktop) {
        sidebarToggleDesktop.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // Event listener untuk mobile toggle (dalam sidebar)
    if (sidebarToggleMobile) {
        sidebarToggleMobile.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // Tutup sidebar saat klik overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }
    
    // Tutup sidebar saat resize ke desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
    
    // Show/hide spinner
    window.showLoading = function() {
        document.getElementById('spinner').style.display = 'block';
    };
    
    window.hideLoading = function() {
        document.getElementById('spinner').style.display = 'none';
    };
    
    // Toast notification
    window.showToast = function(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const container = document.querySelector('.toast-container');
        if (!container) {
            const newContainer = document.createElement('div');
            newContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(newContainer);
        }
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.id = toastId;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    };
    
    // Handle form submissions dengan AJAX
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (form.hasAttribute('data-ajax')) {
            e.preventDefault();
            submitFormAjax(form);
        }
    });
    
    function submitFormAjax(form) {
        var formData = new FormData(form);
        var submitBtn = form.querySelector('button[type="submit"]');
        var originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
        submitBtn.disabled = true;
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                window.showToast(data.message, 'success');
                if (form.dataset.redirect) {
                    setTimeout(function() {
                        window.location.href = form.dataset.redirect;
                    }, 1500);
                }
            } else {
                window.showToast(data.message, 'danger');
            }
        })
        .catch(function(error) {
            window.showToast('Terjadi kesalahan: ' + error.message, 'danger');
        })
        .finally(function() {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
    
    // Auto-hide alerts setelah 5 detik
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popover initialization
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.forEach(function(popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });
});
</script>