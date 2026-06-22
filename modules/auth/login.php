<?php
$pageTitle = "Login Akun";
$customCSS = "
/* Tombol gradasi */
.btn-grad {
    background: linear-gradient(135deg, #F9DA00, #FF9133);
    border: none;
    color: #000 !important;
    font-weight: 600;
    transition: 0.3s;
}
.btn-grad:hover {
    background: linear-gradient(135deg, #FF9133, #F9DA00);
    color: #000 !important;
}
";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/functions-auth.php';

// Jika user sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . base_url('modules/dashboard/dashboard.php'));
    exit();
}

// Check remember token
check_remember_token();

// Proses form login
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Validasi CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $result = login_user($email, $password, $remember);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            
            // Redirect berdasarkan kelengkapan profil
            if ($result['profile_complete']) {
                // Redirect ke URL sebelumnya jika ada
                $redirect_url = $_SESSION['redirect_url'] ?? base_url('modules/dashboard/dashboard.php');
                unset($_SESSION['redirect_url']);
                header("Location: " . $redirect_url);
            } else {
                header("Location: " . base_url('modules/profile/profile.php'));
            }
            exit();
        } else {
            $error = $result['message'];
        }
    }
}

// Get messages from session
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

include __DIR__ . '/header-auth.php';
?>

<div class="auth-header">
    <h1 class="display-5 mb-3">Login Akun</h1>
    <p class="lead mb-0">Masuk ke Portal Pendaftaran Ujian Dinas (UDIN) dan Ujian Penyesuaian Kenaikan Pangkat (UPKP).</p>
</div>

<div class="auth-body">
    <div class="row">
        <!-- Login Form -->
        <div class="col-lg-7">
            <?php if ($error): ?>
                <div class="alert alert-danger auth-alert alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success auth-alert alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="login" value="1">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="email" 
                               placeholder="contoh@instansi.go.id" required
                               value="<?php echo sanitize_output($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" 
                               placeholder="Masukkan password" required id="password">
                        <button type="button" class="input-group-text" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <a href="#" class="text-decoration-none small">Lupa password?</a>
                    </div>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">
                        Ingat saya di perangkat ini
                    </label>
                </div>
                
                <button type="submit" class="btn btn-grad w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk
                </button>
                
                <div class="text-center">
                    <p class="mb-0">
                        Belum punya akun? 
                        <a href="<?php echo base_url('modules/auth/register.php'); ?>" class="text-decoration-none fw-bold">
                            Daftar di sini
                        </a>
                    </p>
                </div>
            </form>
            
            <hr class="my-4">
        </div>
        
        <!-- Informasi & Bantuan -->
        <div class="col-lg-5">
            <div class="bg-light rounded p-4 h-100">
                <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>Informasi Ujian</h4>
                
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center p-3 bg-white rounded">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Jadwal Ujian</h6>
                                <p class="mb-0 small text-muted">UDIN & UPKP dilaksanakan sesuai periode yang ditetapkan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center p-3 bg-white rounded">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Persyaratan</h6>
                                <p class="mb-0 small text-muted">Lengkapi dokumen sesuai ketentuan sebelum mendaftar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center p-3 bg-white rounded">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Bantuan</h6>
                                <p class="mb-0 small text-muted">Hubungi helpdesk di nomor 021-1500-123 atau email info@udin-upkp.go.id</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <p class="text-center mb-0">
                    <small class="text-muted">Portal ini hanya untuk ASN yang terdaftar</small>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$customJS = <<<JS
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    var passwordInput = document.getElementById('password');
    var icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Form submission
document.getElementById('loginForm').addEventListener('submit', function(e) {
    showFormLoading('loginForm');
});
JS;

include __DIR__ . '/footer-auth.php';
?>