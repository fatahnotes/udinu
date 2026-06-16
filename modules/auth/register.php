<?php
$pageTitle = "Registrasi Akun";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/functions-auth.php';

// Jika user sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . base_url('modules/dashboard/dashboard.php'));
    exit();
}

// Proses form registrasi
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Validasi CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);
        
        if (!$terms) {
            $error = 'Anda harus menyetujui Syarat & Ketentuan.';
        } else {
            $result = register_user($full_name, $email, $password, $confirm_password);
            
            if ($result['success']) {
                $success = $result['message'];
                // Redirect ke login setelah 3 detik
                header("refresh:3;url=" . base_url('modules/auth/login.php'));
            } else {
                $error = $result['message'];
            }
        }
    }
}

include __DIR__ . '/header-auth.php';
?>

<div class="auth-header">
    <h1 class="display-5 mb-3">Registrasi Akun Baru</h1>
    <p class="lead mb-0">Bergabunglah dengan program Guru Garuda untuk membangun pendidikan Indonesia yang lebih baik.</p>
</div>

<div class="auth-body">
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
            <p class="mt-2 mb-0">Anda akan diarahkan ke halaman login dalam 3 detik...</p>
        </div>
    <?php endif; ?>
    
    <form id="registerForm" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <input type="hidden" name="register" value="1">
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               placeholder="Masukkan nama lengkap" required
                               value="<?php echo sanitize_output($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="form-text text-muted">Minimal 3 karakter</div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="contoh@email.com" required
                               value="<?php echo sanitize_output($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-text text-muted">Email harus valid dan aktif</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Masukkan password" required>
                        <button type="button" class="input-group-text" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength mt-2">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="form-text text-muted">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Minimal 8 karakter, huruf besar, kecil, angka, dan simbol
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" placeholder="Ulangi password" required>
                    </div>
                    <div id="passwordMatch" class="form-text"></div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    Saya menyetujui <a href="#" class="text-decoration-none">Syarat & Ketentuan</a> dan 
                    <a href="#" class="text-decoration-none">Kebijakan Privasi</a> yang berlaku
                </label>
            </div>
        </div>
        
        <div class="form-group mb-4">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter" checked>
                <label class="form-check-label" for="newsletter">
                    Saya ingin menerima informasi terbaru tentang seleksi dan program Guru Garuda via email
                </label>
            </div>
        </div>
        
        <button type="submit" class="btn-auth w-100 mb-3" id="registerBtn">
            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
        </button>
        
        <div class="text-center">
            <p class="mb-0">
                Sudah punya akun? 
                <a href="<?php echo base_url('modules/auth/login.php'); ?>" class="text-decoration-none fw-bold">
                    Login di sini
                </a>
            </p>
        </div>
    </form>
    
    <hr class="my-4">
    
    
</div>

<?php
$customJS = <<<JS
// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    var password = this.value;
    var strengthBar = document.getElementById('passwordStrengthBar');
    var strength = 0;
    
    // Length check
    if (password.length >= 8) strength += 25;
    
    // Contains lowercase
    if (/[a-z]/.test(password)) strength += 25;
    
    // Contains uppercase
    if (/[A-Z]/.test(password)) strength += 25;
    
    // Contains numbers or symbols
    if (/[0-9]/.test(password)) strength += 13;
    if (/[^A-Za-z0-9]/.test(password)) strength += 12;
    
    // Update strength bar
    strengthBar.style.width = strength + '%';
    strengthBar.className = 'password-strength-bar ';
    
    if (strength <= 25) {
        strengthBar.classList.add('strength-weak');
    } else if (strength <= 50) {
        strengthBar.classList.add('strength-fair');
    } else if (strength <= 75) {
        strengthBar.classList.add('strength-good');
    } else {
        strengthBar.classList.add('strength-strong');
    }
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    var password = document.getElementById('password').value;
    var confirmPassword = this.value;
    var matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
        matchDiv.innerHTML = '';
        matchDiv.className = 'form-text text-muted';
    } else if (password === confirmPassword) {
        matchDiv.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Password cocok';
        matchDiv.className = 'form-text text-success';
    } else {
        matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Password tidak cocok';
        matchDiv.className = 'form-text text-danger';
    }
});

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

// Form validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirmPassword = document.getElementById('confirm_password').value;
    var terms = document.getElementById('terms');
    
    // Check password match
    if (password !== confirmPassword) {
        e.preventDefault();
        showToast('Password dan konfirmasi password tidak cocok!', 'danger');
        return false;
    }
    
    // Check terms acceptance
    if (!terms.checked) {
        e.preventDefault();
        showToast('Anda harus menyetujui Syarat & Ketentuan!', 'danger');
        return false;
    }
    
    // Password strength validation
    if (password.length < 8) {
        e.preventDefault();
        showToast('Password minimal 8 karakter!', 'danger');
        return false;
    }
    
    // Show loading
    showFormLoading('registerForm');
});
JS;

include __DIR__ . '/footer-auth.php';
?>