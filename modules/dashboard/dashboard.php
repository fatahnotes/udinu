<?php
// modules/dashboard/dashboard.php — Dashboard Premium
$pageTitle = "Dashboard";
$activePage = "dashboard";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

require_login();
if ($_SESSION['user_role'] === 'USER') {
    require_complete_profile();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];
$profile_complete = $_SESSION['profile_complete'] ?? false;

$db = get_db_connection();

// Profile photo
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

log_activity('DASHBOARD_ACCESS', "User accessed dashboard", $user_id);

$stats = [];
$announcements = [];
$active_vacancies = [];
$user_submission_statuses = [];

try {
    if ($user_role === 'USER') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['submissions'] = $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT s.id as submission_id, s.vacancy_id, s.status
            FROM submissions s
            INNER JOIN vacancies v ON s.vacancy_id = v.id
            WHERE s.user_id = ?
            AND v.is_active = TRUE
            AND v.open_date <= CURRENT_DATE
            AND v.close_date >= CURRENT_DATE
            ORDER BY s.updated_at DESC
        ");
        $stmt->execute([$user_id]);
        foreach ($stmt->fetchAll() as $sub) {
            $user_submission_statuses[$sub['vacancy_id']] = [
                'status' => $sub['status'],
                'submission_id' => $sub['submission_id'],
            ];
        }
    }

    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM vacancies
        WHERE is_active = TRUE
        AND open_date <= CURRENT_DATE
        AND close_date >= CURRENT_DATE
    ");
    $stmt->execute();
    $stats['active_vacancies'] = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM announcements WHERE is_published = TRUE ORDER BY published_at DESC LIMIT 5");
    $stmt->execute();
    $announcements = $stmt->fetchAll();

    if ($user_role === 'USER') {
        $stmt = $db->prepare("
            SELECT v.*, vt.type_name
            FROM vacancies v
            JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
            WHERE v.is_active = TRUE
            AND v.open_date <= CURRENT_DATE
            AND v.close_date >= CURRENT_DATE
            ORDER BY v.close_date ASC
            LIMIT 6
        ");
        $stmt->execute();
        $active_vacancies = $stmt->fetchAll();
    }

    if ($user_role === 'SUPERADMIN') {
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_deleted = FALSE");
        $stats['total_users'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COUNT(*) FROM vacancies");
        $stats['total_vacancies'] = $stmt->fetchColumn();
        $stmt = $db->query("SELECT COUNT(*) FROM submissions");
        $stats['total_submissions'] = $stmt->fetchColumn();
    }

    // Fetch latest submission for progress tracker (USER only)
    $tracker_submission = null;
    if ($user_role === 'USER') {
        try {
            $stmt = $db->prepare("SELECT s.*, v.title as vacancy_title, vt.type_name FROM submissions s JOIN vacancies v ON s.vacancy_id = v.id JOIN vacancy_types vt ON v.vacancy_type_id = vt.id WHERE s.user_id = ? ORDER BY s.updated_at DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $tracker_submission = $stmt->fetch();
        } catch (Exception $e) { /* may not exist */ }
    }
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

include __DIR__ . '/header-dashboard.php';
?>

<!-- ============================================================ -->
<!-- WELCOME BANNER -->
<!-- ============================================================ -->
<div class="dash-banner mb-4">
    <div class="dash-banner-bg"></div>
    <div class="dash-banner-content">
        <div class="dash-banner-text">
            <h2 class="dash-banner-title">Selamat Datang, <?php echo htmlspecialchars($user_name); ?>!</h2>
            <p class="dash-banner-sub">
                Anda login sebagai <strong><?php echo $user_role; ?></strong> di Sistem Pendaftaran UDIN & UPKP
                <?php if (!$profile_complete && $user_role === 'USER'): ?>
                    <br><span class="badge bg-warning text-dark mt-1"><i class="fas fa-exclamation-triangle me-1"></i>Lengkapi profil Anda untuk dapat mengikuti seleksi</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="dash-banner-avatar">
            <?php if (!empty($profile_foto)): ?>
                <img src="<?php echo base_url($profile_foto); ?>" alt="Foto">
            <?php else: ?>
                <div class="dash-avatar-placeholder"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($user_role === 'USER' && $tracker_submission): ?>
<!-- ============================================================ -->
<!-- PROGRESS TRACKER — Pizza-style horizontal steps -->
<!-- ============================================================ -->
<?php
$ts = $tracker_submission;
$status = strtolower($ts['status'] ?? 'draft');

// Map 7-stage status to progress index
$status_to_index = [
    'draft' => 0,
    'submitted' => 1,
    'verified_satker' => 2, 'rejected_satker' => 1,
    'verified_pusat' => 3, 'rejected_pusat' => 2,
    'exam_phase' => 4,
    'scoring_phase' => 5,
    'announced' => 6,
    'certified' => 7, 'passed' => 7, 'not_passed' => 6,
];
$currentStage = $status_to_index[$status] ?? 0;
$is_rejected = in_array($status, ['rejected_satker', 'rejected_pusat', 'not_passed']);
$is_passed = in_array($status, ['passed', 'certified']);

$stages = [
    ['icon'=>'fa-file-alt',   'label'=>'Pendaftaran',       'desc'=>'Submit berkas'],
    ['icon'=>'fa-building',   'label'=>'Verifikasi Satker', 'desc'=>'Admin Satker'],
    ['icon'=>'fa-landmark',   'label'=>'Verifikasi Pusat',  'desc'=>'Admin Pusat'],
    ['icon'=>'fa-pencil-alt', 'label'=>'Masa Ujian',        'desc'=>'CAT/Makalah'],
    ['icon'=>'fa-star',       'label'=>'Penilaian',         'desc'=>'Asesor + CAT'],
    ['icon'=>'fa-bullhorn',   'label'=>'Pengumuman',        'desc'=>'Hasil'],
    ['icon'=>'fa-certificate','label'=>'Sertifikat',        'desc'=>'Unduh'],
];
?>
<div class="tracker-wrapper mb-4">
    <div class="tracker-card">
        <div class="tracker-title"><i class="fas fa-route me-2"></i>Status Pendaftaran: <strong><?php echo htmlspecialchars($ts['vacancy_title']); ?></strong> <span class="badge rounded-pill ms-2" style="background:#eef2ff;color:#4f46e5;font-size:0.7rem"><?php echo htmlspecialchars($ts['type_name']??''); ?></span></div>
        <div class="tracker-steps">
            <?php foreach ($stages as $i => $st):
                $cls = '';
                if ($is_rejected && $i === $currentStage) $cls = 'tracker-rejected';
                elseif ($i < $currentStage && !$is_rejected) $cls = 'tracker-done';
                elseif ($i === $currentStage && !$is_rejected) $cls = 'tracker-active';
            ?>
            <div class="tracker-step <?php echo $cls; ?>">
                <div class="tracker-step-circle"><i class="fas <?php echo $st['icon']; ?>"></i></div>
                <div class="tracker-step-label"><?php echo $st['label']; ?></div>
                <div class="tracker-step-desc"><?php echo $st['desc']; ?></div>
                <?php if ($i < 6): ?><div class="tracker-step-line"></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($is_passed): ?>
        <div class="tracker-footer text-center mt-3 pt-2 border-top">
            <span class="badge bg-success rounded-pill px-3 py-2 me-2"><i class="fas fa-check-circle me-1"></i>SELAMAT ANDA LULUS!</span>
            <button class="btn btn-primary btn-sm rounded-pill px-4" onclick="alert('Fitur download sertifikat akan segera tersedia.')"><i class="fas fa-download me-1"></i>Download Sertifikat</button>
        </div>
        <?php elseif ($is_rejected): ?>
        <div class="tracker-footer text-center mt-3 pt-2 border-top">
            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-times-circle me-1"></i>Ditolak — Tetap semangat dan coba lagi!</span>
        </div>
        <?php elseif ($status === 'not_passed'): ?>
        <div class="tracker-footer text-center mt-3 pt-2 border-top">
            <span class="badge bg-warning rounded-pill px-3 py-2"><i class="fas fa-times-circle me-1"></i>Belum Lulus — Tetap semangat!</span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- STATS ROW -->
<!-- ============================================================ -->
<div class="row g-3 mb-4">
    <?php if ($user_role === 'SUPERADMIN'): ?>
    <div class="col-xl-3 col-md-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#eef2ff;color:#4f46e5"><i class="fas fa-users"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                <div class="dash-stat-label">Total User</div>
            </div>
            <a href="<?php echo base_url('modules/admin/user-management.php'); ?>" class="dash-stat-link"><i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-briefcase"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"><?php echo number_format($stats['total_vacancies'] ?? 0); ?></div>
                <div class="dash-stat-label">Total Ujian</div>
            </div>
            <a href="<?php echo base_url('modules/admin/vacancy-management.php'); ?>" class="dash-stat-link"><i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-file-alt"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"><?php echo number_format($stats['total_submissions'] ?? 0); ?></div>
                <div class="dash-stat-label">Total Pendaftaran</div>
            </div>
            <span class="dash-stat-link text-muted"><i class="fas fa-eye"></i></span>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-bullhorn"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"><?php echo number_format($stats['active_vacancies'] ?? 0); ?></div>
                <div class="dash-stat-label">Ujian Aktif</div>
            </div>
            <span class="dash-stat-link text-muted"><i class="fas fa-eye"></i></span>
        </div>
    </div>
    <?php elseif ($user_role === 'USER'): ?>
    <div class="col-md-3 col-sm-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#eef2ff;color:#4f46e5"><i class="fas fa-user-tie"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"><?php echo $user_role; ?></div>
                <div class="dash-stat-label">Role Anda</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-file-alt"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"><?php echo $stats['submissions'] ?? 0; ?></div>
                <div class="dash-stat-label">Pendaftaran</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-bullhorn"></i></div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"><?php echo $stats['active_vacancies'] ?? 0; ?></div>
                <div class="dash-stat-label">Ujian Tersedia</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background:<?php echo $profile_complete ? '#d1fae5' : '#fee2e2'; ?>;color:<?php echo $profile_complete ? '#059669' : '#dc2626'; ?>">
                <i class="fas fa-<?php echo $profile_complete ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size:0.9rem"><?php echo $profile_complete ? 'LENGKAP' : 'BELUM'; ?></div>
                <div class="dash-stat-label">Status Profil</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- MAIN CONTENT -->
<!-- ============================================================ -->
<div class="row">
    <div class="col-lg-8">

        <?php if ($user_role === 'SUPERADMIN'): ?>
        <!-- Quick Actions -->
        <div class="dash-section-card">
            <div class="dash-section-header"><i class="fas fa-bolt text-amber me-2"></i>Aksi Cepat</div>
            <div class="dash-quick-actions">
                <a href="<?php echo base_url('modules/admin/vacancy-management.php'); ?>" class="dash-quick-btn">
                    <span class="dash-quick-icon" style="background:#eef2ff;color:#4f46e5"><i class="fas fa-plus-circle"></i></span>
                    <span>Tambah Ujian</span>
                </a>
                <a href="<?php echo base_url('modules/admin/user-management.php'); ?>" class="dash-quick-btn">
                    <span class="dash-quick-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-user-plus"></i></span>
                    <span>Tambah User</span>
                </a>
                <a href="#" class="dash-quick-btn">
                    <span class="dash-quick-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-envelope"></i></span>
                    <span>Buat Pengumuman</span>
                </a>
                <a href="<?php echo base_url('modules/admin/exam-master.php'); ?>" class="dash-quick-btn">
                    <span class="dash-quick-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-database"></i></span>
                    <span>Master Data</span>
                </a>
                <a href="#" class="dash-quick-btn">
                    <span class="dash-quick-icon" style="background:#fce7f3;color:#db2777"><i class="fas fa-chart-bar"></i></span>
                    <span>Laporan</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user_role === 'USER' && !empty($active_vacancies)): ?>
        <!-- Active Exams -->
        <div class="dash-section-card">
            <div class="dash-section-header">
                <i class="fas fa-file-alt text-blue me-2"></i>Ujian Aktif
                <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="ms-auto text-decoration-none small fw-semibold" style="color:#0d6efd">Lihat Semua <i class="fas fa-chevron-right fa-xs"></i></a>
            </div>
            <div class="row g-3">
                <?php foreach ($active_vacancies as $vacancy):
                    $days_left = ceil((strtotime($vacancy['close_date']) - time()) / (60 * 60 * 24));
                    $max_applicants = $vacancy['max_applicants'];
                    $current_applicants = $vacancy['current_applicants'] ?? 0;
                    $has_quota = !empty($max_applicants) && $max_applicants > 0;
                    $quota_pct = $has_quota ? min(100, ($current_applicants / $max_applicants) * 100) : 0;
                    $quota_full = $has_quota && $quota_pct >= 100;
                    $user_status_data = $user_submission_statuses[$vacancy['id']] ?? null;
                    $user_status = $user_status_data['status'] ?? null;
                    $user_submission_id = $user_status_data['submission_id'] ?? 0;
                    $urgency = $days_left <= 7 ? 'danger' : ($days_left <= 14 ? 'warning' : 'info');
                    
                    // Status labels for display
                    $status_labels = [
                        'draft' => 'Draft', 'submitted' => 'Dikirim',
                        'verified_satker' => 'Lolos Satker', 'rejected_satker' => 'Ditolak Satker',
                        'verified_pusat' => 'Lolos Pusat', 'rejected_pusat' => 'Ditolak Pusat',
                        'exam_phase' => 'Masa Ujian', 'scoring_phase' => 'Penilaian',
                        'announced' => 'Diumumkan', 'certified' => 'Tersertifikasi',
                        'passed' => 'Lulus', 'not_passed' => 'Tidak Lulus',
                    ];
                    $status_display = $status_labels[$user_status] ?? ucfirst($user_status ?? '');
                    $status_css = str_replace('_', '-', strtolower($user_status ?? ''));
                ?>
                <div class="col-md-4">
                    <div class="dash-exam-card">
                        <?php if ($user_status): ?>
                        <span class="dash-exam-status dash-exam-status-<?php echo $status_css; ?>"><?php echo $status_display; ?></span>
                        <?php endif; ?>
                        <div class="dash-exam-type"><?php echo htmlspecialchars($vacancy['type_name']); ?></div>
                        <h6 class="dash-exam-title"><?php echo htmlspecialchars($vacancy['title']); ?></h6>
                        <p class="dash-exam-desc"><?php echo htmlspecialchars(mb_strlen($vacancy['description']??'') > 80 ? mb_substr($vacancy['description'], 0, 80).'...' : ($vacancy['description']??'')); ?></p>
                        <div class="dash-exam-meta">
                            <span class="dash-days-badge dash-days-<?php echo $urgency; ?>"><i class="fas fa-clock me-1"></i><?php echo $days_left; ?> hari lagi</span>
                            <span class="text-muted small"><?php echo date('d/m', strtotime($vacancy['open_date'])); ?> – <?php echo date('d/m', strtotime($vacancy['close_date'])); ?></span>
                        </div>
                        <?php if ($has_quota): ?>
                        <div class="dash-exam-quota">
                            <div class="d-flex justify-content-between small mb-1"><span>Kuota</span><span><?php echo $current_applicants; ?>/<?php echo $max_applicants; ?></span></div>
                            <div class="progress" style="height:4px"><div class="progress-bar bg-<?php echo $quota_full?'danger':($quota_pct>=80?'warning':'success'); ?>" style="width:<?php echo $quota_pct; ?>%"></div></div>
                        </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <?php if ($profile_complete): ?>
                                <?php if ($quota_full): ?>
                                    <button class="btn btn-outline-danger btn-sm rounded-pill w-100" disabled><i class="fas fa-times-circle me-1"></i>Kuota Penuh</button>
                                <?php elseif ($user_status === 'draft'): ?>
                                    <a href="<?php echo base_url('modules/submission/apply.php?id='.$vacancy['id']); ?>" class="btn btn-warning btn-sm rounded-pill w-100"><i class="fas fa-edit me-1"></i>Lanjutkan Draft</a>
                                <?php elseif ($user_status === 'rejected_satker'): ?>
                                    <a href="<?php echo base_url('modules/submission/apply.php?id='.$vacancy['id']); ?>" class="btn btn-warning btn-sm rounded-pill w-100"><i class="fas fa-redo me-1"></i>Ajukan Ulang</a>
                                <?php elseif ($user_status): ?>
                                    <a href="<?php echo base_url('modules/submission/apply.php?submission_id=' . $user_submission_id . '&view=1'); ?>" class="btn btn-outline-primary btn-sm rounded-pill w-100"><i class="fas fa-eye me-1"></i>Lihat Status</a>
                                <?php else: ?>
                                    <a href="<?php echo base_url('modules/submission/apply.php?id='.$vacancy['id']); ?>" class="btn btn-primary btn-sm rounded-pill w-100"><i class="fas fa-paper-plane me-1"></i>Daftar</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?php echo base_url('modules/profile/profile.php?mode=edit'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill w-100"><i class="fas fa-exclamation-circle me-1"></i>Lengkapi Profil</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Announcements -->
        <div class="dash-section-card">
            <div class="dash-section-header">
                <i class="fas fa-bullhorn text-green me-2"></i>Pengumuman Terbaru
                <a href="#" class="ms-auto text-decoration-none small fw-semibold" style="color:#0d6efd">Lihat Semua <i class="fas fa-chevron-right fa-xs"></i></a>
            </div>
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $ann): ?>
                <div class="dash-announce-item">
                    <div class="dash-announce-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1"><?php echo htmlspecialchars($ann['title']); ?></h6>
                            <span class="badge rounded-pill" style="background:#eef2ff;color:#4f46e5;font-size:0.7rem"><?php echo htmlspecialchars($ann['announcement_type']); ?></span>
                        </div>
                        <p class="text-muted small mb-1"><?php echo htmlspecialchars(mb_strlen($ann['content']) > 150 ? mb_substr($ann['content'], 0, 150).'...' : $ann['content']); ?></p>
                        <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i><?php echo date('d M Y H:i', strtotime($ann['published_at'])); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4"><i class="fas fa-newspaper fa-2x text-muted mb-2 d-block"></i><p class="text-muted small mb-0">Tidak ada pengumuman saat ini.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- RIGHT SIDEBAR -->
    <!-- ============================================================ -->
    <div class="col-lg-4">
        <?php if ($user_role === 'SUPERADMIN'): ?>
        <div class="dash-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-server me-2"></i>Sistem Overview</h6>
            <div class="dash-sidebar-list">
                <div class="dash-sidebar-item"><span>Status Sistem</span><span class="badge bg-success rounded-pill">ONLINE</span></div>
                <div class="dash-sidebar-item"><span>Total User</span><span class="fw-semibold"><?php echo number_format($stats['total_users'] ?? 0); ?></span></div>
                <div class="dash-sidebar-item"><span>Total Ujian</span><span class="fw-semibold"><?php echo number_format($stats['total_vacancies'] ?? 0); ?></span></div>
                <div class="dash-sidebar-item"><span>Total Pendaftaran</span><span class="fw-semibold"><?php echo number_format($stats['total_submissions'] ?? 0); ?></span></div>
                <div class="dash-sidebar-item"><span>Ujian Aktif</span><span class="fw-semibold text-success"><?php echo number_format($stats['active_vacancies'] ?? 0); ?></span></div>
            </div>
        </div>
        <div class="dash-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-link me-2"></i>Menu Admin</h6>
            <div class="dash-sidebar-links">
                <a href="<?php echo base_url('modules/admin/user-management.php'); ?>" class="dash-sidebar-link"><i class="fas fa-users me-2"></i>Manajemen User</a>
                <a href="<?php echo base_url('modules/admin/vacancy-management.php'); ?>" class="dash-sidebar-link"><i class="fas fa-file-alt me-2"></i>Manajemen Ujian</a>
                <a href="<?php echo base_url('modules/admin/exam-master.php'); ?>" class="dash-sidebar-link"><i class="fas fa-database me-2"></i>Master Data Ujian</a>
                <a href="#" class="dash-sidebar-link"><i class="fas fa-check-circle me-2"></i>Verifikasi Berkas</a>
                <a href="#" class="dash-sidebar-link"><i class="fas fa-star me-2"></i>Penilaian</a>
                <a href="#" class="dash-sidebar-link"><i class="fas fa-chart-line me-2"></i>Laporan & Statistik</a>
            </div>
        </div>
        <div class="dash-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Info Teknis</h6>
            <div class="dash-sidebar-list">
                <div class="dash-sidebar-item"><span>Versi Sistem</span><span class="badge rounded-pill" style="background:#eef2ff;color:#4f46e5">v1.0</span></div>
                <div class="dash-sidebar-item"><span>PHP</span><span class="text-muted small"><?php echo phpversion(); ?></span></div>
                <div class="dash-sidebar-item"><span>Login Terakhir</span><span class="text-muted small"><?php echo date('d/m/Y H:i'); ?></span></div>
                <div class="dash-sidebar-item"><span>Keamanan</span><span class="badge bg-success rounded-pill">AKTIF</span></div>
            </div>
        </div>

        <?php elseif ($user_role === 'USER'): ?>
        <div class="dash-sidebar-card text-center">
            <?php if (!empty($profile_foto)): ?>
                <img src="<?php echo base_url($profile_foto); ?>" alt="Foto" class="dash-sidebar-avatar mb-2">
            <?php else: ?>
                <div class="dash-sidebar-avatar mb-2 mx-auto" style="background:linear-gradient(135deg,#1a3a5c,#0d6efd);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
            <?php endif; ?>
            <h6 class="mb-1"><?php echo htmlspecialchars($user_name); ?></h6>
            <p class="text-muted small"><?php echo htmlspecialchars($user_email); ?></p>
            <?php if ($profile_complete): ?>
                <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i>PROFIL LENGKAP</span>
                <p class="text-muted small mt-2 mb-0">Siap mendaftar ujian.</p>
            <?php else: ?>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fas fa-exclamation-circle me-1"></i>PROFIL BELUM LENGKAP</span>
                <a href="<?php echo base_url('modules/profile/profile.php?mode=edit'); ?>" class="btn btn-primary btn-sm rounded-pill px-4 mt-2"><i class="fas fa-edit me-1"></i>Lengkapi</a>
            <?php endif; ?>
        </div>

        <div class="dash-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-info-circle me-2"></i>Info Sistem</h6>
            <div class="dash-sidebar-list">
                <div class="dash-sidebar-item"><span>Status</span><span class="badge bg-success rounded-pill">ONLINE</span></div>
                <div class="dash-sidebar-item"><span>Login Terakhir</span><span class="text-muted small"><?php echo date('d/m/Y H:i'); ?></span></div>
                <div class="dash-sidebar-item"><span>Keamanan</span><span class="badge bg-success rounded-pill">AKTIF</span></div>
            </div>
        </div>

        <div class="dash-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-link me-2"></i>Tautan Cepat</h6>
            <div class="dash-sidebar-links">
                <a href="<?php echo base_url('modules/profile/profile.php'); ?>" class="dash-sidebar-link"><i class="fas fa-user me-2"></i>Profil Saya</a>
                <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="dash-sidebar-link"><i class="fas fa-file-alt me-2"></i>Pendaftaran Saya</a>
                <a href="#" class="dash-sidebar-link"><i class="fas fa-calendar-alt me-2"></i>Jadwal Seleksi</a>
                <a href="#" class="dash-sidebar-link"><i class="fas fa-question-circle me-2"></i>FAQ & Bantuan</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- STYLES -->
<!-- ============================================================ -->
<style>
/* === Welcome Banner === */
.dash-banner {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    background: #fff;
}
.dash-banner-bg {
    height: 100px;
    background: linear-gradient(135deg, #0a2463 0%, #123499 25%, #1a56db 50%, #2563eb 75%, #3b82f6 100%);
    position: relative;
}
.dash-banner-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.dash-banner-content {
    position: relative;
    padding: 20px 28px;
    margin-top: -40px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    background: #fff;
}
.dash-banner-text { flex: 1; min-width: 200px; }
.dash-banner-title { color: #1e293b; font-size: 1.3rem; font-weight: 700; margin-bottom: 4px; }
.dash-banner-sub { color: #64748b; font-size: 0.88rem; margin-bottom: 0; }
.dash-banner-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid #fff;
    box-shadow: 0 3px 12px rgba(0,0,0,0.12);
    flex-shrink: 0;
    background: #e2e8f0;
}
.dash-banner-avatar img { width: 100%; height: 100%; object-fit: cover; }
.dash-avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1a3a5c, #0d6efd);
    color: #fff;
    font-size: 1.6rem;
    font-weight: 700;
}

/* === Stat Cards === */
.dash-stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.2s;
    position: relative;
}
.dash-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.dash-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.dash-stat-info { flex: 1; }
.dash-stat-value { font-size: 1.4rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.dash-stat-label { font-size: 0.78rem; color: #94a3b8; font-weight: 500; }
.dash-stat-link {
    position: absolute;
    top: 12px;
    right: 14px;
    font-size: 0.8rem;
    color: #94a3b8;
    transition: color 0.2s;
    text-decoration: none;
}
.dash-stat-card:hover .dash-stat-link { color: #0d6efd; }

/* === Section Cards === */
.dash-section-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}
.dash-section-header {
    font-size: 0.88rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
}

/* === Quick Actions === */
.dash-quick-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.dash-quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 18px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #edf2f9;
    text-decoration: none;
    color: #334155;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.2s;
    flex: 1;
    min-width: 100px;
    text-align: center;
}
.dash-quick-btn:hover { background: #fff; border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); color: #1e293b; text-decoration: none; }
.dash-quick-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: transform 0.2s;
}
.dash-quick-btn:hover .dash-quick-icon { transform: scale(1.1); }

/* === Exam Cards === */
.dash-exam-card {
    background: #fff;
    border: 1px solid #edf2f9;
    border-radius: 12px;
    padding: 18px;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.2s;
    position: relative;
}
.dash-exam-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-2px); }
.dash-exam-type {
    font-size: 0.7rem;
    font-weight: 600;
    color: #4f46e5;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.dash-exam-title {
    font-size: 0.92rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.dash-exam-desc {
    font-size: 0.78rem;
    color: #94a3b8;
    margin-bottom: 10px;
    flex: 1;
}
.dash-exam-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
.dash-exam-quota { margin-bottom: 4px; }
.dash-exam-status {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 0.65rem;
    padding: 2px 10px;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.dash-exam-status-draft { background: #f1f5f9; color: #64748b; }
.dash-exam-status-submitted { background: #dbeafe; color: #2563eb; }
.dash-exam-status-verified { background: #d1fae5; color: #059669; }
.dash-exam-status-rejected { background: #fee2e2; color: #dc2626; }
.dash-exam-status-accepted { background: #d1fae5; color: #059669; }

.dash-days-badge {
    font-size: 0.72rem;
    padding: 2px 10px;
    border-radius: 12px;
    font-weight: 600;
}
.dash-days-danger { background: #fee2e2; color: #dc2626; }
.dash-days-warning { background: #fef3c7; color: #d97706; }
.dash-days-info { background: #dbeafe; color: #2563eb; }

/* === Announcements === */
.dash-announce-item {
    display: flex;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.dash-announce-item:last-child { border-bottom: 0; }
.dash-announce-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #d1fae5;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

/* === Sidebar === */
.dash-sidebar-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin-bottom: 16px;
}
.dash-sidebar-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e2e8f0;
}
.dash-sidebar-list { display: flex; flex-direction: column; gap: 2px; }
.dash-sidebar-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.85rem;
    color: #334155;
}
.dash-sidebar-item + .dash-sidebar-item { border-top: 1px solid #f8fafc; }
.dash-sidebar-links { display: flex; flex-direction: column; gap: 2px; }
.dash-sidebar-link {
    display: flex;
    align-items: center;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #475569;
    text-decoration: none;
    transition: all 0.15s;
}
.dash-sidebar-link:hover { background: #f1f5f9; color: #1e293b; text-decoration: none; }
.dash-sidebar-link i { color: #94a3b8; width: 18px; text-align: center; }

/* === Progress Tracker (Pizza-style) === */
.tracker-card {
    background: #fff;
    border-radius: 20px;
    padding: 24px 28px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.tracker-title {
    font-size: 0.88rem;
    color: #64748b;
    margin-bottom: 24px;
    text-align: center;
}
.tracker-steps {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    position: relative;
}
.tracker-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
    position: relative;
    z-index: 1;
    min-width: 0;
}
.tracker-step-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #f1f5f9;
    border: 3px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #94a3b8;
    position: relative;
    z-index: 2;
    transition: all 0.3s;
    flex-shrink: 0;
}
.tracker-step-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #94a3b8;
    margin-top: 8px;
    line-height: 1.3;
}
.tracker-step-desc {
    font-size: 0.65rem;
    color: #cbd5e1;
    margin-top: 2px;
    line-height: 1.3;
}
.tracker-step-line {
    position: absolute;
    top: 24px;
    left: calc(50% + 30px);
    right: calc(-50% + 30px);
    height: 3px;
    background: #e2e8f0;
    z-index: 0;
}
.tracker-done .tracker-step-circle { background: #d1fae5; border-color: #059669; color: #059669; }
.tracker-done .tracker-step-label { color: #059669; }
.tracker-done .tracker-step-desc { color: #6ee7b7; }
.tracker-done .tracker-step-line { background: #059669; }
.tracker-active .tracker-step-circle {
    background: #1a3a5c;
    border-color: #1a3a5c;
    color: #fff;
    box-shadow: 0 0 0 6px rgba(26,58,92,0.12);
    animation: trackerPulse 2s infinite;
}
.tracker-active .tracker-step-label { color: #1e293b; }
.tracker-active .tracker-step-desc { color: #64748b; }
.tracker-rejected .tracker-step-circle { background: #fee2e2; border-color: #dc2626; color: #dc2626; }
.tracker-rejected .tracker-step-label { color: #dc2626; }
.tracker-rejected .tracker-step-desc { color: #f87171; }
@keyframes trackerPulse {
    0%,100% { box-shadow: 0 0 0 6px rgba(26,58,92,0.12); }
    50% { box-shadow: 0 0 0 14px rgba(26,58,92,0.04); }
}

/* === Responsive === */
@media (max-width: 768px) {
    .dash-banner-content { flex-direction: column; align-items: flex-start; }
    .dash-banner-avatar { align-self: center; }
    .dash-stat-card { padding: 16px; }
    .dash-stat-value { font-size: 1.2rem; }
    .dash-quick-actions { gap: 8px; }
    .dash-quick-btn { min-width: 80px; padding: 12px; }
    .tracker-steps { overflow-x: auto; padding-bottom: 10px; }
    .tracker-step { flex: 0 0 auto; min-width: 80px; }
    .tracker-step-label { font-size: 0.65rem; }
    .tracker-step-desc { display: none; }
    .tracker-step-circle { width: 38px; height: 38px; font-size: 0.8rem; }
    .tracker-step-line { top: 19px; left: calc(50% + 22px); right: calc(-50% + 22px); }
}
</style>

<?php include __DIR__ . '/footer-dashboard.php'; ?>
