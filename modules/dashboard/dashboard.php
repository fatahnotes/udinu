<?php
// modules/dashboard/dashboard.php
$pageTitle = "Dashboard";
$activePage = "dashboard";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

// Require login dan profil lengkap
require_login();
if ($_SESSION['user_role'] === 'USER') {
    require_complete_profile();
}

// Get user info dari session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];
$profile_complete = $_SESSION['profile_complete'] ?? false;

// Database connection
$db = get_db_connection();

// Ambil foto profil user
$profile_foto = null;
try {
    $stmt = $db->prepare("SELECT foto FROM profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch();
    if ($profile_data && !empty($profile_data['foto'])) {
        $profile_foto = $profile_data['foto'];
    }
} catch (PDOException $e) {
    error_log("Profile foto fetch error: " . $e->getMessage());
}

// Log dashboard access
log_activity('DASHBOARD_ACCESS', "User accessed dashboard", $user_id);

// Stats dan data lainnya
$stats = [];
$announcements = [];
$active_vacancies = [];
$user_submission_statuses = []; // Untuk menyimpan status submission per vacancy

try {
    // Get user submissions count dan status untuk setiap vacancy (hanya untuk USER)
    if ($user_role === 'USER') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['submissions'] = $stmt->fetchColumn();
        
        // Ambil status submission untuk setiap vacancy yang aktif
        $stmt = $db->prepare("
            SELECT s.vacancy_id, s.status 
            FROM submissions s
            INNER JOIN vacancies v ON s.vacancy_id = v.id
            WHERE s.user_id = ? 
            AND v.is_active = TRUE 
            AND v.open_date <= CURRENT_DATE 
            AND v.close_date >= CURRENT_DATE
            ORDER BY s.updated_at DESC
        ");
        $stmt->execute([$user_id]);
        $user_submissions = $stmt->fetchAll();
        
        // Konversi ke array dengan vacancy_id sebagai key
        foreach ($user_submissions as $sub) {
            $user_submission_statuses[$sub['vacancy_id']] = $sub['status'];
        }
    }
    
    // Get active vacancies untuk semua user
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM vacancies 
        WHERE is_active = TRUE 
        AND open_date <= CURRENT_DATE 
        AND close_date >= CURRENT_DATE
    ");
    $stmt->execute();
    $stats['active_vacancies'] = $stmt->fetchColumn();
    
    // Get announcements
    $stmt = $db->prepare("
        SELECT * FROM announcements 
        WHERE is_published = TRUE 
        ORDER BY published_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll();
    
    // Get detail active vacancies untuk dashboard
    if ($user_role === 'USER') {
        $stmt = $db->prepare("
            SELECT v.*, vt.type_name 
            FROM vacancies v
            JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
            WHERE v.is_active = TRUE 
            AND v.open_date <= CURRENT_DATE 
            AND v.close_date >= CURRENT_DATE
            ORDER BY v.close_date ASC
            LIMIT 3
        ");
        $stmt->execute();
        $active_vacancies = $stmt->fetchAll();
    }
    
    // Untuk SUPERADMIN, get stats tambahan
    if ($user_role === 'SUPERADMIN') {
        // Total users
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_deleted = FALSE");
        $stats['total_users'] = $stmt->fetchColumn();
        
        // Total vacancies
        $stmt = $db->query("SELECT COUNT(*) FROM vacancies");
        $stats['total_vacancies'] = $stmt->fetchColumn();
        
        // Total submissions
        $stmt = $db->query("SELECT COUNT(*) FROM submissions");
        $stats['total_submissions'] = $stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

include __DIR__ . '/header-dashboard.php';
?>

<!-- Welcome Card -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">Selamat Datang, <?php echo htmlspecialchars($user_name); ?>!</h2>
                    <p class="mb-0">
                        Anda login sebagai <strong><?php echo $user_role; ?></strong> di Sistem Pendaftaran UDIN & UPKP.
                        <?php if (!$profile_complete && $user_role === 'USER'): ?>
                            <br><i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Lengkapi profil Anda</strong> untuk dapat mengikuti seleksi.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="user-avatar" style="width: 100px; height: 100px; font-size: 40px; display: inline-flex; background: white; color: #0d6efd; border-radius: 50%; overflow: hidden; border: 3px solid white;">
                        <?php if (!empty($profile_foto)): ?>
                            <img src="<?php echo base_url($profile_foto); ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white;">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($user_role === 'SUPERADMIN'): ?>
<!-- Superadmin Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo $stats['total_users'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total User</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-briefcase fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo $stats['total_vacancies'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total Lowongan</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo $stats['total_submissions'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total Pendaftaran</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-bullhorn fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo $stats['active_vacancies'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Lowongan Aktif</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($user_role === 'USER'): ?>
<!-- User Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-user-tie fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo strtoupper($user_role); ?></h3>
                    <p class="text-muted mb-0">Role Anda</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo $stats['submissions'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Yang Anda Daftar</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-bullhorn fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo $stats['active_vacancies'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Lowongan Tersedia</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="dashboard-card">
            <div class="d-flex align-items-center">
                <div class="rounded-circle <?php echo $profile_complete ? 'bg-info' : 'bg-danger'; ?> text-white d-flex align-items-center justify-content-center me-3" 
                     style="width: 60px; height: 60px;">
                    <i class="fas fa-<?php echo $profile_complete ? 'check-circle' : 'exclamation-circle'; ?> fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-1"><?php echo $profile_complete ? 'LENGKAP' : 'BELUM'; ?></h3>
                    <p class="text-muted mb-0">Status Profil</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <?php if ($user_role === 'USER' && !empty($active_vacancies)): ?>
        <!-- Lowongan Aktif -->
        <div class="dashboard-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-briefcase me-2"></i>Lowongan Aktif</h4>
                <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="text-primary text-decoration-none small">Lihat Semua</a>
            </div>
            
            <div class="row g-3">
                <?php foreach ($active_vacancies as $vacancy): 
                    $days_left = ceil((strtotime($vacancy['close_date']) - time()) / (60 * 60 * 24));
                    $progress = (($days_left <= 30) ? $days_left : 30) / 30 * 100;
                    
                    // Hitung progress kuota pendaftar
                    $max_applicants = $vacancy['max_applicants'];
                    $current_applicants = $vacancy['current_applicants'] ?? 0;
                    
                    // Jika max_applicants null atau 0, artinya tidak terbatas
                    $has_quota = !empty($max_applicants) && $max_applicants > 0;
                    $quota_percentage = $has_quota ? min(100, ($current_applicants / $max_applicants) * 100) : 0;
                    $quota_color = 'success'; // Default warna
                    
                    // Tentukan warna berdasarkan persentase kuota
                    if ($quota_percentage >= 100) {
                        $quota_color = 'danger'; // Penuh
                    } elseif ($quota_percentage >= 80) {
                        $quota_color = 'warning'; // Hampir penuh
                    } elseif ($quota_percentage >= 50) {
                        $quota_color = 'info'; // Setengah terisi
                    }
                    
                    // Status kuota untuk tampilan
                    $quota_status = '';
                    if (!$has_quota) {
                        $quota_status = 'Kuota: Tidak Terbatas';
                    } elseif ($quota_percentage >= 100) {
                        $quota_status = 'Kuota Penuh';
                    } else {
                        $quota_status = 'Kuota: ' . $current_applicants . '/' . $max_applicants;
                    }
                    
                    // Cek status submission user untuk lowongan ini
                    $user_submission_status = isset($user_submission_statuses[$vacancy['id']]) ? $user_submission_statuses[$vacancy['id']] : null;
                    
                    // Tentukan warna badge berdasarkan status
                    $status_badge_class = '';
                    $status_text = '';
                    if ($user_submission_status) {
                        switch(strtoupper($user_submission_status)) {
                            case 'DRAFT':
                                $status_badge_class = 'bg-secondary';
                                $status_text = 'Draft';
                                break;
                            case 'SUBMITTED':
                            case 'TERKIRIM':
                                $status_badge_class = 'bg-primary';
                                $status_text = 'Terkirim';
                                break;
                            case 'REVIEW':
                            case 'DIREVIEW':
                                $status_badge_class = 'bg-info';
                                $status_text = 'Direview';
                                break;
                            case 'APPROVED':
                            case 'DISETUJUI':
                                $status_badge_class = 'bg-success';
                                $status_text = 'Disetujui';
                                break;
                            case 'REJECTED':
                            case 'DITOLAK':
                                $status_badge_class = 'bg-danger';
                                $status_text = 'Ditolak';
                                break;
                            case 'PENDING':
                                $status_badge_class = 'bg-warning';
                                $status_text = 'Pending';
                                break;
                            default:
                                $status_badge_class = 'bg-secondary';
                                $status_text = $user_submission_status;
                        }
                    }
                ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm position-relative">
                        <!-- Badge Status Submission (di pojok kanan atas) -->
                        <?php if ($user_submission_status): ?>
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge <?php echo $status_badge_class; ?> status-badge" 
                                  style="font-size: 0.7rem; padding: 4px 8px; border-radius: 12px;"
                                  title="Status pendaftaran Anda">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <!-- Badge dan Info -->
                            <div class="mb-3">
                                <div class="mb-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($vacancy['type_name']); ?></span>
                                </div>
                                <div>
                                    <span class="badge bg-<?php echo $days_left <= 7 ? 'danger' : ($days_left <= 14 ? 'warning' : 'info'); ?>">
                                        <i class="fas fa-clock me-1"></i><?php echo $days_left; ?> hari lagi
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Judul -->
                            <h6 class="card-title vacancy-title">
                                <?php echo htmlspecialchars($vacancy['title']); ?>
                            </h6>
                            
                            <!-- Deskripsi -->
                            <div class="flex-grow-1 mb-3">
                                <p class="card-text small text-muted vacancy-description">
                                    <?php 
                                    $description = htmlspecialchars($vacancy['description'] ?? '');
                                    if (strlen($description) > 80) {
                                        echo substr($description, 0, 80) . '...';
                                    } else {
                                        echo $description;
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <!-- Progress Bar Sisa Waktu -->
                            <div class="mb-3">
                                <!-- Sisa Waktu -->
                                <div class="mb-2">
                                    <div class="small text-muted d-flex justify-content-between mb-1">
                                        <span>Sisa Waktu Pendaftaran</span>
                                        <span><?php echo $days_left; ?> hari</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo $days_left <= 7 ? 'danger' : ($days_left <= 14 ? 'warning' : 'success'); ?>" 
                                             style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>
                                
                                <!-- Kuota Pendaftar (hanya tampil jika ada batasan) -->
                                <?php if ($has_quota): ?>
                                <div class="mb-2">
                                    <div class="small text-muted d-flex justify-content-between mb-1">
                                        <span>Kuota Pendaftar</span>
                                        <span><?php echo $current_applicants; ?>/<?php echo $max_applicants; ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-<?php echo $quota_color; ?>" 
                                             style="width: <?php echo $quota_percentage; ?>%"></div>
                                    </div>
                                    <div class="small text-center mt-1">
                                        <?php if ($quota_percentage >= 100): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Kuota Penuh
                                            </span>
                                        <?php elseif ($quota_percentage >= 80): ?>
                                            <span class="text-warning">
                                                <i class="fas fa-exclamation-circle me-1"></i>Hampir Penuh
                                            </span>
                                        <?php else: ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Masih Tersedia
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="mb-2">
                                    <div class="small text-muted d-flex justify-content-between mb-1">
                                        <span>Kuota Pendaftar</span>
                                        <span class="text-success">
                                            <i class="fas fa-infinity me-1"></i>Tidak Terbatas
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Tanggal -->
                                <div class="small text-muted d-flex justify-content-between mt-2">
                                    <span>Buka: <?php echo date('d/m', strtotime($vacancy['open_date'])); ?></span>
                                    <span>Tutup: <?php echo date('d/m', strtotime($vacancy['close_date'])); ?></span>
                                </div>
                            </div>
                            
                            <!-- Tombol Aksi -->
                            <div class="mt-auto">
                                <?php if ($profile_complete): ?>
                                    <?php if ($has_quota && $quota_percentage >= 100): ?>
                                        <button class="btn btn-danger btn-sm w-100" disabled>
                                            <i class="fas fa-times-circle me-1"></i>Kuota Penuh
                                        </button>
                                    <?php elseif ($user_submission_status): ?>
                                        <?php if (strtoupper($user_submission_status) == 'DRAFT'): ?>
                                            <a href="<?php echo base_url('modules/submission/apply.php?id=' . $vacancy['id']); ?>" 
                                               class="btn btn-warning btn-sm w-100">
                                                <i class="fas fa-edit me-1"></i>Lanjutkan Draft
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo base_url('modules/submission/view.php?id=' . $vacancy['id']); ?>" 
                                               class="btn btn-info btn-sm w-100">
                                                <i class="fas fa-eye me-1"></i>Lihat Status
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?php echo base_url('modules/submission/apply.php?id=' . $vacancy['id']); ?>" 
                                           class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-paper-plane me-1"></i>Daftar Sekarang
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?php echo base_url('modules/profile/profile.php?mode=edit'); ?>" 
                                       class="btn btn-secondary btn-sm w-100">
                                        <i class="fas fa-exclamation-circle me-1"></i>Lengkapi Profil Dulu
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Announcements -->
        <div class="dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Pengumuman Terbaru</h4>
                <a href="#" class="text-primary text-decoration-none small">Lihat Semua</a>
            </div>
            
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($announcement['announcement_type']); ?></span>
                        </div>
                        <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 200) . '...')); ?></p>
                        <div class="small text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d M Y H:i', strtotime($announcement['published_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada pengumuman saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-lg-4">

    <!-- Profile Status Card -->
        <?php if ($user_role === 'USER'): ?>
        <div class="dashboard-card mt-4">
            <div class="card-header">
                <h4><i class="fas fa-user-check me-2"></i>Status Profil</h4>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <?php if (!empty($profile_foto)): ?>
                        <img src="<?php echo base_url($profile_foto); ?>" alt="Foto Profil" 
                             style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #0d6efd;">
                    <?php else: ?>
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); 
                                  display: flex; align-items: center; justify-content: center; color: white; font-size: 30px; margin: 0 auto;">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h5><?php echo htmlspecialchars($user_name); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($user_email); ?></p>
                
                <div class="mt-3">
                    <?php if ($profile_complete): ?>
                        <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">
                            <i class="fas fa-check-circle me-1"></i>PROFIL LENGKAP
                        </span>
                        <p class="text-muted mt-2 mb-0">Profil Anda sudah lengkap dan siap untuk mendaftar.</p>
                    <?php else: ?>
                        <span class="badge bg-warning" style="font-size: 1rem; padding: 8px 16px;">
                            <i class="fas fa-exclamation-circle me-1"></i>PROFIL BELUM LENGKAP
                        </span>
                        <p class="text-muted mt-2 mb-0">Lengkapi profil Anda untuk dapat mendaftar.</p>
                        <a href="<?php echo base_url('modules/profile/profile.php?mode=edit'); ?>" 
                           class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-edit me-1"></i>Lengkapi Sekarang
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="dashboard-card mb-4">
            <div class="card-header">
                <h4><i class="fas fa-info-circle me-2"></i>Info Sistem</h4>
            </div>
            
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Status Sistem</span>
                    <span class="badge bg-success">ONLINE</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Login Terakhir</span>
                    <span><?php echo date('d/m/Y H:i'); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Keamanan</span>
                    <span class="badge bg-success">AKTIF</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>Versi Sistem</span>
                    <span>v1.0</span>
                </li>
            </ul>
        </div>
        
        <!-- Quick Links -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-link me-2"></i>Tautan Cepat</h4>
            </div>
            
            <div class="list-group list-group-flush">
                <a href="<?php echo base_url('modules/profile/profile.php'); ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2 text-primary"></i>Profil Saya
                </a>
                <?php if ($user_role === 'USER'): ?>
                <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-alt me-2 text-primary"></i>Pendaftaran Saya
                </a>
                <?php endif; ?>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>Jadwal Seleksi
                </a>
                <a href="#" class="list-group-item list-group-item-action">
                    <i class="fas fa-question-circle me-2 text-primary"></i>FAQ & Bantuan
                </a>
            </div>
        </div>
        
        
        <?php endif; ?>
    </div>
</div>

<style>
.list-group-item {
    border: none;
    padding: 12px 0;
    color: #495057;
    transition: all 0.3s;
}

.list-group-item:hover {
    color: #0d6efd;
    background: none;
    padding-left: 10px;
}

.btn-dashboard, .btn-outline-dashboard.btn {
    display: flex;
    align-items: center;
    justify-content: center;
}

.border-bottom {
    border-color: #e9ecef !important;
}

.badge {
    padding: 6px 12px;
    font-weight: 500;
}

/* Perbaikan styling untuk card lowongan */
.card {
    background: white;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    border-radius: 10px;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    border-color: #0d6efd;
}

.card-body {
    background: white;
    border-radius: 10px;
}

/* JUDUL CARD - UPDATED: Allow text wrapping */
.vacancy-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 12px;
    line-height: 1.4;
    /* Allow text to wrap to multiple lines */
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    /* Limit to 3 lines max */
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 3.4em; /* Approx height for 3 lines */
}

/* Deskripsi */
.vacancy-description {
    color: #6c757d;
    line-height: 1.6;
    font-size: 0.9rem;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}

/* Badge status submission */
.status-badge {
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.5);
}

/* Progress bar styling */
.progress {
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 10px;
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
}

.btn-secondary, .btn-info, .btn-warning, .btn-danger {
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 500;
}

.btn-info {
    background: linear-gradient(135deg, #0dcaf0 0%, #0ba8cc 100%);
    border: none;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    border: none;
}

.flex-grow-1 {
    flex: 1;
}

.mt-auto {
    margin-top: auto;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .vacancy-title {
        min-height: auto;
        -webkit-line-clamp: 4; /* Allow more lines on mobile */
    }
    
    .vacancy-description {
        min-height: auto;
    }
    
    .col-md-4 {
        margin-bottom: 20px;
    }
    
    .status-badge {
        font-size: 0.6rem !important;
        padding: 3px 6px !important;
    }
}

/* For better text wrapping on all elements */
.text-wrap {
    word-wrap: break-word;
    white-space: normal;
    overflow-wrap: break-word;
}

/* Ensure badges also wrap if needed */
.badge {
    white-space: normal;
    word-wrap: break-word;
    max-width: 100%;
}

/* User avatar in welcome card */
.user-avatar img {
    transition: transform 0.3s ease;
}

.user-avatar:hover img {
    transform: scale(1.05);
}

/* Profile status card */
.card-body.text-center h5 {
    font-weight: 600;
    color: #2c3e50;
}
</style>

<?php 
// Tambahkan JavaScript khusus untuk dashboard
$customJS .= <<<JS
// Dashboard specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Update waktu login
    const loginTimeElement = document.querySelector('[data-time="login-time"]');
    if (loginTimeElement) {
        setInterval(function() {
            const now = new Date();
            loginTimeElement.textContent = 
                now.toLocaleDateString('id-ID') + ' ' + now.toLocaleTimeString('id-ID');
        }, 60000);
    }
    
    // Smooth scroll untuk anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Tooltip untuk judul yang terpotong
    document.querySelectorAll('.text-truncate').forEach(element => {
        if (element.offsetWidth < element.scrollWidth) {
            element.setAttribute('title', element.textContent);
        }
    });
    
    // Hover effect untuk card lowongan
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Tooltip untuk badge status
    document.querySelectorAll('.status-badge').forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title');
            if (title) {
                // Create tooltip
                const tooltip = document.createElement('div');
                tooltip.className = 'custom-tooltip';
                tooltip.textContent = title;
                tooltip.style.position = 'absolute';
                tooltip.style.background = 'rgba(0,0,0,0.8)';
                tooltip.style.color = 'white';
                tooltip.style.padding = '5px 10px';
                tooltip.style.borderRadius = '4px';
                tooltip.style.fontSize = '12px';
                tooltip.style.zIndex = '9999';
                tooltip.style.whiteSpace = 'nowrap';
                
                const rect = this.getBoundingClientRect();
                tooltip.style.top = (rect.top - 35) + 'px';
                tooltip.style.left = (rect.left + rect.width/2 - tooltip.offsetWidth/2) + 'px';
                
                document.body.appendChild(tooltip);
                this.tooltipElement = tooltip;
            }
        });
        
        badge.addEventListener('mouseleave', function() {
            if (this.tooltipElement) {
                this.tooltipElement.remove();
                this.tooltipElement = null;
            }
        });
    });
});
JS;

include __DIR__ . '/footer-dashboard.php'; 
?>