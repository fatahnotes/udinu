<?php
require_once __DIR__ . '/../../config/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . base_url('modules/auth/login.php'));
    exit;
}

// Fungsi untuk mendapatkan menu berdasarkan role
function get_sidebar_menu($user_role) {
    $menu = [];
    
    switch ($user_role) {
        case 'SUPERADMIN':
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard'],
                ['icon' => 'briefcase', 'label' => 'Manajemen Lowongan', 'url' => '../admin/vacancy-management.php', 'active' => 'vacancy-management'],
                ['icon' => 'users', 'label' => 'Manajemen User', 'url' => '../admin/user-management.php', 'active' => 'user-management'],
                ['icon' => 'cogs', 'label' => 'Konfigurasi', 'url' => '../admin/configuration.php', 'active' => 'configuration'],
                ['icon' => 'check-circle', 'label' => 'Verifikasi Berkas', 'url' => '../verification/verification.php', 'active' => 'verification'],
                ['icon' => 'star', 'label' => 'Penilaian', 'url' => '../scoring/scoring.php', 'active' => 'scoring'],
                ['icon' => 'bullhorn', 'label' => 'Pengumuman', 'url' => '../announcement/announcement.php', 'active' => 'announcement']
            ];
            break;
            
        case 'ADMIN_VERIFIKATOR':
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard'],
                ['icon' => 'check-circle', 'label' => 'Verifikasi Berkas', 'url' => '../verification/verification.php', 'active' => 'verification'],
                ['icon' => 'users', 'label' => 'Daftar Pendaftar', 'url' => '../verification/applicant-list.php', 'active' => 'applicant-list'],
                ['icon' => 'chart-bar', 'label' => 'Statistik', 'url' => '../verification/statistics.php', 'active' => 'statistics']
            ];
            break;
            
        case 'ASSESSOR':
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard'],
                ['icon' => 'star', 'label' => 'Penilaian', 'url' => '../scoring/scoring.php', 'active' => 'scoring'],
                ['icon' => 'clipboard-check', 'label' => 'Hasil Penilaian', 'url' => '../scoring/results.php', 'active' => 'scoring-results'],
                ['icon' => 'chart-line', 'label' => 'Analisis', 'url' => '../scoring/analysis.php', 'active' => 'scoring-analysis']
            ];
            break;
            
        case 'USER':
        default:
            $menu = [
                ['icon' => 'tachometer-alt', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'active' => 'dashboard'],
                ['icon' => 'user', 'label' => 'Profil', 'url' => '../profile/profile.php', 'active' => 'profile'],
                ['icon' => 'briefcase', 'label' => 'Pendaftaran', 'url' => '../submission/submission.php', 'active' => 'submission'],
                ['icon' => 'history', 'label' => 'Status Proses', 'url' => '../submission/status.php', 'active' => 'status'],
                ['icon' => 'file-alt', 'label' => 'Dokumen', 'url' => '../submission/documents.php', 'active' => 'documents']
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
        /* Dashboard Styles */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-logo {
            display: block;
            margin: 0 auto 2px;
            text-decoration: none;
        }

        .sidebar-logo img {
            max-width: 300px;
            height: auto;
            display: block;
            margin: 0 auto;
            transition: all 0.3s;
        }

        .sidebar-logo:hover img {
            transform: scale(1.05);
        }

        .sidebar-header h3 {
            color: white;
            margin: 10px 0 5px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .sidebar-header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            margin: 5px 0 0;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            text-decoration: none;
        }

        .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .nav-link .badge {
            margin-left: auto;
            font-size: 0.7em;
        }

        .nav-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 15px 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: all 0.3s;
        }

        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-navbar .navbar-brand {
            font-weight: 600;
            color: #0d6efd;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
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
            padding: 30px;
            min-height: calc(100vh - 70px);
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
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                width: 250px;
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-navbar {
                padding: 15px 20px;
            }
            
            .content-area {
                padding: 20px;
            }
            
            .user-info {
                display: none;
            }
            
            .sidebar-logo img {
                max-width: 100px;
            }
        }

        /* Mobile toggle button */
        .sidebar-toggle {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            display: none;
            margin-bottom: 15px;
            width: 100%;
            text-align: center;
        }

        @media (max-width: 768px) {
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

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <!-- Logo OSDM Kemdiktisaintek -->
                <a href="<?php echo base_url('index.php'); ?>" class="sidebar-logo" aria-label="OSDM Kemdiktisaintek">
                    <img src="<?php echo base_url('img/logo-dikti.svg'); ?>" alt="Logo OSDM Kemdiktisaintek" onerror="this.onerror=null; this.src='<?php echo base_url('assets/img/logo-placeholder.png'); ?>'; this.alt='Logo Kemdiktisaintek';">
                </a>
            <button class="sidebar-toggle" id="sidebarToggleMobile">
                <i class="fas fa-bars me-2"></i>Menu
            </button>
            
            <div class="sidebar-header">           
                <h3><i class="fas fa-chalkboard-teacher me-2"></i>UDIN & UPKP</h3>
                <p>Dashboard <?php echo isset($_SESSION['user_role']) ? strtoupper($_SESSION['user_role']) : ''; ?></p>
            </div>
            
            <div class="sidebar-menu">
                <ul class="nav flex-column">
                    <?php foreach ($sidebar_menu as $item): ?>
                    <li class="nav-item">
                        <a href="<?php echo base_url('modules/dashboard/' . $item['url']); ?>" 
                           class="nav-link <?php echo ($activePage == $item['active']) ? 'active' : ''; ?>">
                            <i class="fas fa-<?php echo $item['icon']; ?>"></i> 
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    
                    <li class="nav-item">
                        <hr class="nav-divider">
                    </li>
                    

                    
                                        <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-file-pdf"></i> Panduan
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="fas fa-question-circle"></i> Pertanyaan
                        </a>
                    </li>
                    

                    
                    <li class="nav-item">
                        <a href="<?php echo base_url('modules/auth/logout.php'); ?>" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary d-md-none me-3" id="sidebarToggleDesktop">
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
    
    // Fungsi untuk toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('active');
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
    
    // Tutup sidebar saat klik di luar (untuk mobile)
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 768) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = (sidebarToggleDesktop && sidebarToggleDesktop.contains(event.target)) || 
                                   (sidebarToggleMobile && sidebarToggleMobile.contains(event.target));
            
            if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Tutup sidebar saat resize window ke ukuran desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768 && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
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
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.id = toastId;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const container = document.querySelector('.toast-container');
        if (!container) {
            const newContainer = document.createElement('div');
            newContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(newContainer);
            newContainer.appendChild(toast);
        } else {
            container.appendChild(toast);
        }
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    };
    
    // Handle form submissions dengan AJAX
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.hasAttribute('data-ajax')) {
            e.preventDefault();
            submitFormAjax(form);
        }
    });
    
    function submitFormAjax(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
        submitBtn.disabled = true;
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                if (form.dataset.redirect) {
                    setTimeout(() => {
                        window.location.href = form.dataset.redirect;
                    }, 1500);
                }
            } else {
                showToast(data.message, 'danger');
            }
        })
        .catch(error => {
            showToast('Terjadi kesalahan: ' + error.message, 'danger');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }
    
    // Auto-hide alerts setelah 5 detik
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popover initialization
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
</script>