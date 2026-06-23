<?php
// modules/submission/submission.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = "Status Pendaftaran";
$activePage = "submission";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';
require_once __DIR__ . '/functions-submission.php';

// Hanya USER yang bisa mengakses
require_login();
if ($_SESSION['user_role'] !== 'USER') {
    header('Location: ' . base_url('modules/dashboard/dashboard.php'));
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle delete submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission'])) {
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (validate_csrf_token($csrf_token)) {
        // Verify submission belongs to user and is a draft
        $sql = "SELECT status FROM submissions WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$submission_id, $user_id]);
        $submission = $stmt->fetch();
        
        if ($submission && $submission['status'] === 'draft') {
            if (cancel_submission_draft($db, $submission_id, $user_id)) {
                $success = 'Draft pendaftaran berhasil dihapus.';
                // Refresh page to update list
                header("Refresh: 2; url=" . $_SERVER['PHP_SELF']);
            } else {
                $error = 'Gagal menghapus draft pendaftaran.';
            }
        } else {
            $error = 'Hanya draft yang bisa dihapus atau draft tidak ditemukan.';
        }
    } else {
        $error = 'Token keamanan tidak valid.';
    }
}

// Get user submissions
$submissions = get_user_submissions($db, $user_id);

// Get active submission if exists
$active_submission = get_user_current_submission($db, $user_id);

// Get files for active submission jika ada
$active_submission_files = [];
if ($active_submission) {
    $active_submission_files = get_submission_files($db, $active_submission['id']);
    // Add view link to each file
    foreach ($active_submission_files as &$file) {
        $file['view_link'] = base_url($file['file_path']);
    }
}

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Status Pendaftaran</h2>
                <?php if (!$active_submission): ?>
                <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn-dashboard">
                    <i class="fas fa-plus me-2"></i>Daftar Ujian Baru
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($error): ?>
    <div class="alert alert-danger dashboard-alert alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success dashboard-alert alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($active_submission): ?>
<!-- Active Submission Card -->
<div class="dashboard-card mb-4">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-briefcase me-2"></i>Pendaftaran Aktif
            </h4>
            <span class="badge bg-<?php 
                echo $active_submission['status'] === 'submitted' ? 'info' : 
                    ($active_submission['status'] === 'verified' ? 'primary' : 
                    ($active_submission['status'] === 'scored' ? 'warning' : 
                    ($active_submission['status'] === 'accepted' ? 'success' : 
                    ($active_submission['status'] === 'rejected' ? 'danger' : 'secondary'))));
            ?>">
                <?php 
                $status_text = [
                    'draft' => 'DRAFT',
                    'submitted' => 'DIKIRIM',
                    'verified' => 'DIVERIFIKASI',
                    'scored' => 'DINILAI',
                    'accepted' => 'DITERIMA',
                    'rejected' => 'DITOLAK'
                ];
                echo $status_text[$active_submission['status']] ?? strtoupper($active_submission['status']);
                ?>
            </span>
        </div>
    </div>
    
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h5 class="text-primary"><?php echo htmlspecialchars($active_submission['title']); ?></h5>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($active_submission['type_name'] ?? 'N/A'); ?></span>
                    <span class="badge bg-info">Kode: <?php echo htmlspecialchars($active_submission['vacancy_code']); ?></span>
                    <span class="badge bg-secondary">ID: #<?php echo $active_submission['id']; ?></span>
                    <span class="badge bg-<?php echo !empty($active_submission_files) ? 'success' : 'warning'; ?>">
                        <i class="fas fa-file-pdf me-1"></i><?php echo count($active_submission_files); ?> dokumen
                    </span>
                </div>
                
                <!-- Progress Timeline -->
                <?php if ($active_submission['status'] !== 'draft'): ?>
                <div class="progress-timeline mb-4">
                    <?php
                    $timeline_steps = [
                        'submitted' => ['icon' => 'paper-plane', 'label' => 'Dikirim', 'date' => $active_submission['submission_date']],
                        'verified' => ['icon' => 'check-circle', 'label' => 'Diverifikasi', 'date' => $active_submission['verification_date']],
                        'scored' => ['icon' => 'star', 'label' => 'Dinilai', 'date' => $active_submission['scoring_date']],
                        'announced' => ['icon' => 'bullhorn', 'label' => 'Pengumuman', 'date' => $active_submission['announcement_date']]
                    ];
                    
                    $current_step = $active_submission['status'];
                    $step_order = ['submitted', 'verified', 'scored', 'announced'];
                    $current_index = array_search($current_step, $step_order);
                    $current_index = $current_index !== false ? $current_index : -1;
                    ?>
                    
                    <div class="timeline">
                        <?php foreach ($timeline_steps as $key => $step): 
                            $step_index = array_search($key, $step_order);
                            $is_active = $step_index <= $current_index;
                            $is_completed = $step_index < $current_index;
                        ?>
                        <div class="timeline-step <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-<?php echo $step['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h6><?php echo $step['label']; ?></h6>
                                <?php if ($step['date']): ?>
                                <p class="text-muted mb-0"><?php echo date('d M Y H:i', strtotime($step['date'])); ?></p>
                                <?php else: ?>
                                <p class="text-muted mb-0">Menunggu...</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Current Status -->
                <div class="alert alert-<?php 
                    echo $active_submission['status'] === 'draft' ? 'warning' : 
                        ($active_submission['status'] === 'accepted' ? 'success' : 
                        ($active_submission['status'] === 'rejected' ? 'danger' : 'info'));
                ?>">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-<?php 
                            echo $active_submission['status'] === 'draft' ? 'exclamation-triangle' : 
                                ($active_submission['status'] === 'accepted' ? 'check-circle' : 
                                ($active_submission['status'] === 'rejected' ? 'times-circle' : 'info-circle'));
                        ?> fa-2x me-3"></i>
                        <div>
                            <h6 class="mb-1">Status Saat Ini: <strong>
                                <?php 
                                $status_display = [
                                    'draft' => 'DRAFT',
                                    'submitted' => 'DIKIRIM',
                                    'verified' => 'DIVERIFIKASI',
                                    'scored' => 'DINILAI',
                                    'accepted' => 'DITERIMA',
                                    'rejected' => 'DITOLAK'
                                ];
                                echo $status_display[$active_submission['status']] ?? strtoupper($active_submission['status']);
                                ?>
                            </strong></h6>
                            <p class="mb-0">
                                <?php 
                                $status_messages = [
                                    'draft' => 'Pendaftaran Anda masih dalam draft. Silakan lengkapi dan submit sebelum tanggal tutup.',
                                    'submitted' => 'Pendaftaran Anda telah diterima dan sedang menunggu verifikasi.',
                                    'verified' => 'Dokumen Anda telah diverifikasi dan sedang menunggu penilaian.',
                                    'scored' => 'Pendaftaran Anda telah dinilai dan sedang menunggu pengumuman.',
                                    'accepted' => 'Selamat! Anda diterima. Silakan cek pengumuman resmi.',
                                    'rejected' => 'Maaf, pendaftaran Anda tidak diterima.'
                                ];
                                echo $status_messages[$active_submission['status']] ?? 'Sedang diproses...';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons for Draft -->
                <?php if ($active_submission['status'] === 'draft'): ?>
                <div class="d-flex gap-3 mt-4">
                    <a href="<?php echo base_url('modules/submission/apply.php?edit=true&submission_id=' . $active_submission['id']); ?>" 
                       class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Lengkapi & Submit
                    </a>
                    
                    <form method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus draft ini? Semua dokumen akan dihapus secara permanen.');">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="submission_id" value="<?php echo $active_submission['id']; ?>">
                        <input type="hidden" name="delete_submission" value="1">
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-trash me-2"></i>Hapus Draft
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <!-- Important Dates -->
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Tanggal Penting</h6>
                        
                        <div class="date-item mb-2">
                            <small class="text-muted">Tanggal Daftar</small>
                            <p class="mb-0"><?php echo date('d M Y H:i', strtotime($active_submission['created_at'])); ?></p>
                        </div>
                        
                        <?php if ($active_submission['submission_date']): ?>
                        <div class="date-item mb-2">
                            <small class="text-muted">Tanggal Submit</small>
                            <p class="mb-0"><?php echo date('d M Y H:i', strtotime($active_submission['submission_date'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="date-item mb-2">
                            <small class="text-muted">Periode Ujian</small>
                            <p class="mb-0">
                                <?php echo date('d M Y', strtotime($active_submission['open_date'])); ?> - 
                                <?php echo date('d M Y', strtotime($active_submission['close_date'])); ?>
                            </p>
                        </div>
                        
                        <?php if ($active_submission['status'] !== 'draft'): ?>
                        <div class="date-item">
                            <small class="text-muted">Status Ujian</small>
                            <p class="mb-0">
                                <?php 
                                $today = date('Y-m-d');
                                if ($today >= $active_submission['open_date'] && $today <= $active_submission['close_date']) {
                                    echo '<span class="badge bg-success">Masih Dibuka</span>';
                                } elseif ($today < $active_submission['open_date']) {
                                    echo '<span class="badge bg-info">Belum Dibuka</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Sudah Ditutup</span>';
                                }
                                ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-3">
                    <?php if ($active_submission['status'] !== 'draft'): ?>
                    <a href="#" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-download me-2"></i>Download Bukti Pendaftaran
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-outline-secondary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <i class="fas fa-question-circle me-2"></i>Bantuan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Uploaded Documents Section -->
<?php if (!empty($active_submission_files)): ?>
<div class="dashboard-card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Dokumen Terunggah</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="45%">Nama File</th>
                        <th width="20%">Ukuran</th>
                        <th width="20%">Tanggal Upload</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_submission_files as $index => $file): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <i class="fas fa-file-pdf text-danger me-2"></i>
                            <span class="small"><?php echo htmlspecialchars($file['file_name']); ?></span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">
                                <?php echo format_submission_bytes($file['file_size']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($file['uploaded_at'])); ?></td>
                        <td>
                            <a href="<?php echo $file['view_link']; ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary"
                               title="Lihat Dokumen">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Submission History -->
<div class="dashboard-card">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Pendaftaran</h4>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="15%">Tanggal</th>
                    <th width="30%">Ujian</th>
                    <th width="15%">Kode</th>
                    <th width="15%">Status</th>
                    <th width="25%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Belum ada riwayat pendaftaran</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($submissions as $sub): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($sub['created_at'])); ?></td>
                    <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($sub['title']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($sub['type_name'] ?? 'N/A'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($sub['vacancy_code']); ?></td>
                    <td>
                        <?php
                        $status_class = [
                            'draft' => 'warning',
                            'submitted' => 'info',
                            'verified' => 'primary',
                            'scored' => 'warning',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            'closed' => 'secondary'
                        ][$sub['display_status']] ?? 'secondary';
                        
                        $status_text = [
                            'draft' => 'DRAFT',
                            'submitted' => 'DIKIRIM',
                            'verified' => 'DIVERIFIKASI',
                            'scored' => 'DINILAI',
                            'accepted' => 'DITERIMA',
                            'rejected' => 'DITOLAK',
                            'closed' => 'DITUTUP'
                        ][$sub['display_status']] ?? strtoupper($sub['display_status']);
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <?php if ($sub['status'] === 'draft'): ?>
                            <a href="<?php echo base_url('modules/submission/apply.php?edit=true&submission_id=' . $sub['id']); ?>" 
                               class="btn btn-warning" title="Lengkapi Draft">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" class="d-inline" 
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus draft ini? Semua dokumen akan dihapus.');">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                <input type="hidden" name="delete_submission" value="1">
                                <button type="submit" class="btn btn-outline-danger" title="Hapus Draft">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-outline-primary view-details-btn" 
                                    data-submission-id="<?php echo $sub['id']; ?>"
                                    title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bantuan Status Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>Status Pendaftaran:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><span class="badge bg-warning">DRAFT</span> - Pendaftaran masih dalam draft, belum dikirim</li>
                    <li class="mb-2"><span class="badge bg-info">DIKIRIM</span> - Pendaftaran sudah dikirim, menunggu verifikasi</li>
                    <li class="mb-2"><span class="badge bg-primary">DIVERIFIKASI</span> - Dokumen sudah diverifikasi, menunggu penilaian</li>
                    <li class="mb-2"><span class="badge bg-warning">DINILAI</span> - Sudah dinilai, menunggu pengumuman</li>
                    <li class="mb-2"><span class="badge bg-success">DITERIMA</span> - Pendaftaran diterima</li>
                    <li class="mb-2"><span class="badge bg-danger">DITOLAK</span> - Pendaftaran ditolak</li>
                </ul>
                
                <h6 class="mt-3">Informasi Penting:</h6>
                <p class="small text-muted mb-0">
                    - Anda hanya dapat memiliki 1 pendaftaran aktif dalam waktu yang sama<br>
                    - Draft dapat dihapus kapan saja sebelum disubmit<br>
                    - Setelah submit, pendaftaran tidak dapat dibatalkan<br>
                    - Semua dokumen harus dalam format PDF, maksimal 5MB per file<br>
                    - Status akan diperbarui sesuai progres seleksi
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
.progress-timeline {
    position: relative;
    padding: 20px 0;
}

.timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 30px;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: #e9ecef;
    z-index: 1;
}

.timeline-step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
}

.timeline-icon {
    width: 60px;
    height: 60px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-size: 1.5rem;
    color: #6c757d;
    border: 3px solid white;
    transition: all 0.3s;
}

.timeline-step.active .timeline-icon {
    background-color: #0d6efd;
    color: white;
    transform: scale(1.1);
}

.timeline-step.completed .timeline-icon {
    background-color: #198754;
    color: white;
}

.timeline-content h6 {
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.date-item:not(:last-child) {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 10px;
}

.table td {
    vertical-align: middle;
}

.btn-group form {
    display: inline-block;
}

@media (max-width: 768px) {
    .timeline {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .timeline::before {
        display: none;
    }
    
    .timeline-step {
        display: flex;
        align-items: center;
        text-align: left;
        margin-bottom: 20px;
        width: 100%;
    }
    
    .timeline-icon {
        margin: 0 15px 0 0;
        min-width: 60px;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View details button
    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const submissionId = this.dataset.submissionId;
            loadSubmissionDetails(submissionId);
        });
    });
    
    function loadSubmissionDetails(submissionId) {
        const detailsContent = document.getElementById('detailsContent');
        detailsContent.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Memuat detail pendaftaran...</p>
            </div>
        `;
        
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        modal.show();
        
        // In a real implementation, you would fetch details via AJAX
        // For now, we'll just show a simple message
        setTimeout(() => {
            detailsContent.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Fitur detail pendaftaran akan tersedia dalam versi berikutnya.
                </div>
            `;
        }, 1000);
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-danger)').forEach(alert => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
});
</script>

<?php include __DIR__ . '/../dashboard/footer-dashboard.php'; ?>