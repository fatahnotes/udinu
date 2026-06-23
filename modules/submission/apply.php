<?php
// modules/submission/apply.php
// Error reporting handled by config.php

// Define base path for includes
$base_path = dirname(__DIR__, 2);

// Load configuration FIRST
require_once $base_path . '/config/config.php';

// Set page variables AFTER loading config
$pageTitle = "Pendaftaran Lowongan";
$activePage = "submission";
$customCSS = base_url('modules/submission/css-apply.css');
$customJS = base_url('modules/submission/js-apply.js');

// Load auth functions
require_once $base_path . '/modules/auth/functions-auth.php';
require_once __DIR__ . '/functions-submission.php';

// Hanya USER yang bisa mengakses
require_login();
if ($_SESSION['user_role'] !== 'USER') {
    header('Location: ' . base_url('modules/dashboard/dashboard.php'));
    exit;
}

// Cek profil lengkap
require_complete_profile();

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$warning = '';

// Get vacancy ID from URL
$vacancy_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
$is_edit = isset($_GET['edit']);

if ($vacancy_id <= 0 && $submission_id <= 0) {
    header('Location: ' . base_url('modules/dashboard/dashboard.php'));
    exit;
}

// Initialize variables
$show_form = false;
$vacancy = null;
$formations = [];
$documents = [];
$requirements = [];
$existing_files = [];
$selected_formation_id = 0;

// If editing existing submission
if ($is_edit && $submission_id > 0) {
    // Get submission details
    $submission = get_submission_details($db, $submission_id, $user_id);
    
    if (!$submission) {
        $error = 'Draft tidak ditemukan.';
    } elseif ($submission['status'] !== 'draft') {
        $error = 'Hanya draft yang dapat diedit. Status saat ini: ' . $submission['status'];
    } else {
        $vacancy_id = $submission['vacancy_id'];
        $eligibility = can_apply_to_vacancy($db, $user_id, $vacancy_id);
        
        if (!$eligibility['can_apply']) {
            $error = $eligibility['reason'];
        } else {
            $vacancy = $eligibility['vacancy'];
            $formations = get_vacancy_formations($db, $vacancy_id);
            $selected_formation_id = $submission['formation_id'] ?? 0;
            $show_form = true;
            $documents = get_vacancy_documents($db, $vacancy_id);
            $requirements = get_vacancy_requirements($db, $vacancy_id);
            $existing_files = get_submission_files($db, $submission_id);
        }
    }
}
// If new application
elseif ($vacancy_id > 0) {
    // Check if user can apply
    $eligibility = can_apply_to_vacancy($db, $user_id, $vacancy_id);

    if (!$eligibility['can_apply']) {
        $error = $eligibility['reason'];
    } else {
        $vacancy = $eligibility['vacancy'];
        $formations = get_vacancy_formations($db, $vacancy_id);
        $show_form = true;
        
        // Get vacancy details
        $documents = get_vacancy_documents($db, $vacancy_id);
        $requirements = get_vacancy_requirements($db, $vacancy_id);
        
        // Check if user already has a draft
        if (isset($eligibility['submission_id'])) {
            $submission_id = $eligibility['submission_id'];
            $existing_files = get_submission_files($db, $submission_id);
            // Get formation_id from existing submission
            $submission = get_submission_details($db, $submission_id, $user_id);
            $selected_formation_id = $submission['formation_id'] ?? 0;
        } else {
            // Create draft if not exists
            $submission_id = create_submission_draft($db, $user_id, $vacancy_id);
        }
        
        if (!$submission_id) {
            $error = 'Gagal memulai pendaftaran. Silakan coba lagi.';
            $show_form = false;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $error = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } elseif (isset($_POST['submit_application'])) {
        // Validate formation selection
        $formation_id = intval($_POST['formation_id'] ?? 0);
        
        if ($formation_id <= 0 && !empty($formations)) {
            $error = 'Harap pilih formasi yang akan dilamar.';
        } else {
            // Validate all required files
            $files_data = [];
            $has_errors = false;
            
            foreach ($documents as $doc) {
                $field_name = 'document_' . $doc['id'];
                
                // Check if file is required and not uploaded
                if ($doc['is_required']) {
                    $has_file = false;
                    
                    // Check if new file uploaded
                    if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] !== UPLOAD_ERR_NO_FILE) {
                        $has_file = true;
                    }
                    // Check if existing file exists (for edit mode)
                    elseif ($is_edit) {
                        foreach ($existing_files as $file) {
                            if (strpos($file['file_name'], $doc['document_code']) === 0) {
                                $has_file = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$has_file) {
                        $error .= "Dokumen {$doc['document_name']} wajib diunggah.<br>";
                        $has_errors = true;
                        continue;
                    }
                }
                
                // Process new file upload
                if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] !== UPLOAD_ERR_NO_FILE) {
                    // Validate file
                    $allowed_types = array_map('trim', explode(',', $doc['file_types']));
                    $max_size = $doc['max_size'];
                    
                    $file_errors = validate_submission_file($_FILES[$field_name], $allowed_types, $max_size);
                    
                    if (!empty($file_errors)) {
                        $error .= "Dokumen {$doc['document_name']}: " . implode(', ', $file_errors) . "<br>";
                        $has_errors = true;
                    } else {
                        // Save file
                        $saved_file = save_submission_file($_FILES[$field_name], $doc['document_code'], $submission_id);
                        
                        if ($saved_file) {
                            $files_data[] = $saved_file;
                        } else {
                            $error .= "Gagal menyimpan dokumen {$doc['document_name']}.<br>";
                            $has_errors = true;
                        }
                    }
                }
            }
            
            if (!$has_errors) {
                // Check if at least one file exists (either new or existing)
                $total_files = count($files_data);
                
                // For edit mode, count existing files too
                if ($is_edit) {
                    $total_files += count($existing_files);
                }
                
                if ($total_files > 0) {
                    if ($is_edit) {
    // Untuk edit mode, jika user klik submit, ubah status draft ke submitted
    $delete_files = $_POST['delete_files'] ?? [];
    
    // Hapus file yang ditandai untuk dihapus
    if (!empty($delete_files)) {
        foreach ($delete_files as $file_id) {
            delete_submission_file($db, intval($file_id), $user_id);
        }
    }
    
    if (submit_application_with_formation($db, $submission_id, $user_id, $formation_id, $files_data)) {
        $success = 'Pendaftaran berhasil dikirim!';
        $show_form = false;
        
        // Log activity
        log_activity('APPLICATION_SUBMITTED', "Submitted application for vacancy ID: $vacancy_id", $user_id);
    } else {
        $error = 'Gagal mengirim pendaftaran. Silakan coba lagi.';
    }
} else {
    // New submission
    if (submit_application_with_formation($db, $submission_id, $user_id, $formation_id, $files_data)) {
                            $success = 'Pendaftaran berhasil dikirim!';
                            $show_form = false;
                            
                            // Log activity
                            log_activity('APPLICATION_SUBMITTED', "Submitted application for vacancy ID: $vacancy_id", $user_id);
                        } else {
                            $error = 'Gagal mengirim pendaftaran. Silakan coba lagi.';
                        }
                    }
                } else {
                    $error = 'Harap unggah minimal satu dokumen.';
                }
            }
        }
    
    } elseif (isset($_POST['cancel_draft'])) {
        // Cancel draft
        if (cancel_submission_draft($db, $submission_id, $user_id)) {
            header('Location: ' . base_url('modules/submission/submission.php'));
            exit;
        } else {
            $error = 'Gagal membatalkan pendaftaran.';
        }
   } elseif (isset($_POST['save_draft'])) {
    // Save draft without submitting
    $formation_id = intval($_POST['formation_id'] ?? 0);
    $files_data = [];
    
    // Log untuk debugging
    error_log("DEBUG: Saving draft - Submission ID: $submission_id, Formation ID: $formation_id");
    
    foreach ($documents as $doc) {
        $field_name = 'document_' . $doc['id'];
        
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] !== UPLOAD_ERR_NO_FILE) {
            // Validate file
            $allowed_types = array_map('trim', explode(',', $doc['file_types']));
            $max_size = $doc['max_size'];
            
            $file_errors = validate_submission_file($_FILES[$field_name], $allowed_types, $max_size);
            
            if (empty($file_errors)) {
                // Save file
                $saved_file = save_submission_file($_FILES[$field_name], $doc['document_code'], $submission_id);
                
                if ($saved_file) {
                    $files_data[] = $saved_file;
                    error_log("DEBUG: File saved: " . $saved_file['name']);
                } else {
                    error_log("ERROR: Failed to save file for document ID: " . $doc['id']);
                }
            } else {
                error_log("ERROR: File validation errors: " . implode(', ', $file_errors));
            }
        }
    }
    
    // Check if we're in edit mode
    $is_edit = isset($_GET['edit']) && $_GET['edit'] == true;
    
    // Update draft
    $delete_files = isset($_POST['delete_files']) ? explode(',', $_POST['delete_files']) : [];
    $delete_files = array_filter($delete_files); // Remove empty values
    
    if (update_submission_draft_with_formation($db, $submission_id, $user_id, $formation_id, $files_data, $delete_files)) {
        $success = 'Draft berhasil disimpan!';
        error_log("DEBUG: Draft saved successfully");
        
        // Refresh existing files
        $existing_files = get_submission_files($db, $submission_id);
    } else {
        $error = 'Gagal menyimpan draft. Silakan coba lagi.';
        error_log("ERROR: Failed to save draft");
    }
}
}
    
   

// Load header dashboard
include $base_path . '/modules/dashboard/header-dashboard.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <?php echo $is_edit ? 'Edit Draft Pendaftaran' : 'Pendaftaran Lowongan'; ?>
                </h2>
                <div>
                    <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="btn-outline-dashboard btn me-2">
                        <i class="fas fa-list me-2"></i>Riwayat Pendaftaran
                    </a>
                    <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn-outline-dashboard btn">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
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

<?php if ($warning): ?>
    <div class="alert alert-warning dashboard-alert alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($warning); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($show_form): ?>
<!-- Apply Form -->
<div class="apply-container">
    <!-- Progress Steps -->
    <div class="dashboard-card mb-4">
        <div class="progress-steps">
            <div class="step active">
                <div class="step-circle">1</div>
                <div class="step-label">Informasi Lowongan</div>
            </div>
            <div class="step active">
                <div class="step-circle">2</div>
                <div class="step-label">Unggah Dokumen</div>
            </div>
            <div class="step">
                <div class="step-circle">3</div>
                <div class="step-label">Konfirmasi</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Vacancy Info -->
        <div class="col-lg-4 mb-4">
            <div class="dashboard-card sticky-top">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Lowongan</h4>
                </div>
                <div class="card-body">
                    <?php if ($vacancy): ?>
                        <h5 class="text-primary"><?php echo htmlspecialchars($vacancy['title']); ?></h5>
                        
                        <div class="vacancy-details small">
                            <div class="detail-item">
                                <strong>Kode:</strong> <?php echo htmlspecialchars($vacancy['vacancy_code']); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Jenis:</strong> <?php echo htmlspecialchars($vacancy['type_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Tahun:</strong> <?php echo htmlspecialchars($vacancy['tahun_angkatan']); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Periode:</strong> <?php echo date('d M Y', strtotime($vacancy['open_date'])); ?> - <?php echo date('d M Y', strtotime($vacancy['close_date'])); ?>
                            </div>
                            <?php if ($vacancy['max_applicants']): ?>
                            <div class="detail-item">
                                <strong>Kuota:</strong> <?php echo $vacancy['current_applicants']; ?>/<?php echo $vacancy['max_applicants']; ?> pendaftar
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($vacancy['description'])): ?>
                        <div class="vacancy-description mt-3">
                            <h6 class="text-primary">Deskripsi:</h6>
                            <p class="small text-muted"><?php echo nl2br(htmlspecialchars($vacancy['description'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($formations)): ?>
                        <div class="vacancy-formations mt-3">
                            <h6 class="text-primary">Formasi Tersedia:</h6>
                            <div class="small">
                                <?php foreach ($formations as $formation): ?>
                                <div class="formation-item mb-1">
                                    <i class="fas fa-user-tie me-1 text-muted"></i>
                                    <?php echo htmlspecialchars($formation['formation_name']); ?>
                                    <span class="badge bg-info ms-2"><?php echo $formation['jumlah']; ?> formasi</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="timeline-container mt-3">
                            <div class="timeline-item <?php echo date('Y-m-d') >= $vacancy['open_date'] ? 'active' : ''; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <h6 class="small mb-1">Pendaftaran Dibuka</h6>
                                    <p class="text-muted mb-0 small"><?php echo date('d M Y', strtotime($vacancy['open_date'])); ?></p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo date('Y-m-d') >= $vacancy['open_date'] && date('Y-m-d') <= $vacancy['close_date'] ? 'active' : ''; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <h6 class="small mb-1">Periode Pendaftaran</h6>
                                    <p class="text-muted mb-0 small"><?php echo date('d M Y', strtotime($vacancy['open_date'])); ?> - <?php echo date('d M Y', strtotime($vacancy['close_date'])); ?></p>
                                </div>
                            </div>
                            <div class="timeline-item <?php echo date('Y-m-d') > $vacancy['close_date'] ? 'active' : ''; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <h6 class="small mb-1">Pendaftaran Ditutup</h6>
                                    <p class="text-muted mb-0 small"><?php echo date('d M Y', strtotime($vacancy['close_date'])); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-<?php echo $is_edit ? 'info' : 'warning'; ?> mt-3 small">
                            <i class="fas fa-<?php echo $is_edit ? 'info-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <strong><?php echo $is_edit ? 'Info:' : 'Peringatan:'; ?></strong> 
                            <?php echo $is_edit ? 'Anda dapat menyimpan draft untuk melanjutkan nanti.' : 'Setelah submit, pendaftaran tidak dapat dibatalkan.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-circle fa-2x text-muted mb-3"></i>
                            <p class="text-muted">Informasi lowongan tidak ditemukan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Existing Files Section (for edit mode) -->
            <?php if ($is_edit && !empty($existing_files)): ?>
            <div class="dashboard-card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Dokumen Terunggah</h5>
                </div>
                <div class="card-body">
                    <div class="existing-files">
                        <?php foreach ($existing_files as $file): ?>
                        <div class="existing-file-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <div class="small">
                                <i class="fas fa-file-<?php echo $file['file_type'] === 'pdf' ? 'pdf' : 'image'; ?> text-primary me-2"></i>
                                <?php echo htmlspecialchars($file['file_name']); ?>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="<?php echo base_url($file['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Lihat Dokumen">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-file-btn" 
                                        data-file-id="<?php echo $file['id']; ?>"
                                        data-file-name="<?php echo htmlspecialchars($file['file_name']); ?>"
                                        title="Hapus Dokumen">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Documents Form -->
        <div class="col-lg-8">
            <form method="POST" action="" enctype="multipart/form-data" id="applyForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                <input type="hidden" name="delete_files" id="deleteFiles" value="">
                
                <!-- Formation Selection -->
                <?php if (!empty($formations)): ?>
                <div class="dashboard-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>
                            Pilih Formasi
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="formation_id" class="form-label small">
                                        <strong>Formasi yang akan dilamar</strong> 
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-sm" id="formation_id" name="formation_id" required>
                                        <option value="">-- Pilih Formasi --</option>
                                        <?php foreach ($formations as $formation): ?>
                                        <option value="<?php echo $formation['id']; ?>" 
                                                <?php echo $selected_formation_id == $formation['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($formation['formation_name']); ?> 
                                            (<?php echo $formation['jumlah']; ?> formasi)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Pilih salah satu formasi yang tersedia di atas. Formasi menentukan posisi yang akan Anda lamar.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Documents Upload -->
                <div class="dashboard-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-file-upload me-2"></i>
                            <?php echo $is_edit ? 'Edit Dokumen Pendaftaran' : 'Unggah Dokumen Persyaratan'; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($documents)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada dokumen yang diperlukan untuk lowongan ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="documents-list">
                                <?php foreach ($documents as $index => $doc): 
                                    $field_name = 'document_' . $doc['id'];
                                    $allowed_types = explode(',', $doc['file_types']);
                                    $max_size_mb = $doc['max_size'] / 1048576;
                                    
                                    // Check if file already exists for this document (edit mode)
                                    $has_existing_file = false;
                                    $existing_file_link = '';
                                    if ($is_edit) {
                                        foreach ($existing_files as $file) {
                                            if (strpos($file['file_name'], $doc['document_code']) !== false) {
                                                $has_existing_file = true;
                                                $existing_file_link = base_url($file['file_path']);
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                <div class="document-item mb-3 p-2 border rounded bg-light">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="document-header d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="mb-0 small fw-bold">
                                                    <?php echo ($index + 1) . '. ' . htmlspecialchars($doc['document_name']); ?>
                                                    <?php if ($doc['is_required']): ?>
                                                    <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <span class="badge bg-<?php echo $doc['is_required'] ? 'danger' : 'warning'; ?> small">
                                                    <?php echo $doc['is_required'] ? 'Wajib' : 'Opsional'; ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($doc['description']): ?>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($doc['description']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="file-requirements small text-muted mb-1">
                                                <i class="fas fa-file me-1"></i>Format: <?php echo strtoupper(implode(', ', $allowed_types)); ?> 
                                                <span class="mx-2">|</span>
                                                <i class="fas fa-weight-hanging me-1"></i>Maks: <?php echo number_format($max_size_mb, 1); ?> MB
                                            </div>
                                            
                                            <?php if ($has_existing_file): ?>
                                            <div class="existing-file-info mt-1">
                                                <div class="alert alert-success alert-sm py-1 px-2 mb-0 d-inline-flex align-items-center">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    <span class="me-2">File sudah diunggah</span>
                                                    <a href="<?php echo $existing_file_link; ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2">
                                                        <i class="fas fa-eye me-1"></i>Lihat
                                                    </a>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="file-upload-area small" id="uploadArea_<?php echo $doc['id']; ?>">
                                                <div class="file-upload-placeholder text-center py-2">
                                                    <?php if ($has_existing_file): ?>
                                                        <div class="text-success">
                                                            <i class="fas fa-check-circle mb-1"></i>
                                                            <p class="mb-0 small">File tersedia</p>
                                                        </div>
                                                    <?php else: ?>
                                                        <i class="fas fa-cloud-upload-alt mb-1"></i>
                                                        <p class="mb-0 small">Klik untuk upload</p>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="file" 
                                                       name="<?php echo $field_name; ?>" 
                                                       id="<?php echo $field_name; ?>" 
                                                       class="file-input" 
                                                       accept="<?php echo '.' . implode(',.', $allowed_types); ?>"
                                                       data-max-size="<?php echo $doc['max_size']; ?>"
                                                       data-document-id="<?php echo $doc['id']; ?>"
                                                       data-document-code="<?php echo $doc['document_code']; ?>"
                                                       <?php echo (!$is_edit && $doc['is_required']) ? 'required' : ''; ?>>
                                            </div>
                                            <div class="file-preview small mt-1" id="preview_<?php echo $doc['id']; ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requirements -->
                <?php if (!empty($requirements)): ?>
                <div class="dashboard-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Persyaratan Lowongan</h4>
                    </div>
                    <div class="card-body">
                        <div class="requirements-list">
                            <?php 
                            $grouped_requirements = [];
                            foreach ($requirements as $req) {
                                $grouped_requirements[$req['requirement_type']][] = $req;
                            }
                            ?>
                            
                            <?php foreach ($grouped_requirements as $type => $reqs): ?>
                            <div class="requirement-group mb-3">
                                <h6 class="text-primary small">
                                    <i class="fas fa-<?php echo $type === 'umum' ? 'list' : 'star'; ?> me-2"></i>
                                    Persyaratan <?php echo ucfirst($type); ?>
                                </h6>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($reqs as $req): ?>
                                    <li class="list-group-item d-flex align-items-start py-1 px-0 small">
                                        <i class="fas fa-check-circle text-success me-2 mt-1 small"></i>
                                        <div>
                                            <span><?php echo htmlspecialchars($req['requirement_text']); ?></span>
                                            <?php if ($req['input_type'] !== 'none'): ?>
                                            <div class="text-muted small">
                                                Input: <?php echo htmlspecialchars($req['input_type']); ?> 
                                                | Wajib: <?php echo $req['is_required'] ? 'Ya' : 'Tidak'; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Terms and Conditions -->
                <div class="dashboard-card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-file-contract me-2"></i>Syarat dan Ketentuan</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" required>
                            <label class="form-check-label small" for="agreeTerms">
                                Saya menyatakan bahwa semua informasi dan dokumen yang saya berikan adalah benar dan sah.
                                Saya memahami bahwa informasi palsu dapat mengakibatkan pembatalan pendaftaran.
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="agreePrivacy" name="agree_privacy" required>
                            <label class="form-check-label small" for="agreePrivacy">
                                Saya setuju dengan <a href="#" class="text-primary">Kebijakan Privasi</a> dan 
                                <a href="#" class="text-primary">Syarat Layanan</a> yang berlaku.
                            </label>
                        </div>
                        
                        <?php if (!$is_edit): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeFinal" name="agree_final" required>
                            <label class="form-check-label small" for="agreeFinal">
                                Saya memahami bahwa setelah submit, pendaftaran <strong>tidak dapat dibatalkan</strong> 
                                dan akan diproses sesuai alur seleksi.
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div class="mb-2">
                                <button type="submit" name="cancel_draft" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-times me-1"></i>Batalkan Pendaftaran
                                </button>
                                
                                <?php if ($is_edit): ?>
                                <button type="submit" name="save_draft" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="fas fa-save me-1"></i>Simpan Draft
                                </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali
                                </a>
                                <button type="submit" name="submit_application" class="btn btn-sm btn-success">
                                    <i class="fas fa-paper-plane me-1"></i>
                                    <?php echo $is_edit ? 'Update & Submit' : 'Submit Pendaftaran'; ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-2 mb-0 small">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Tips:</strong> 
                            <?php if ($is_edit): ?>
                                Anda dapat menyimpan draft untuk melanjutkan nanti, atau langsung submit jika semua dokumen sudah lengkap.
                            <?php else: ?>
                                Pastikan semua dokumen terbaca jelas dan sesuai format sebelum submit.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Submit -->
<div class="modal fade" id="confirmSubmitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Submit Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="mb-3">Apakah Anda yakin ingin mengirim pendaftaran?</h5>
                    <p class="text-muted">
                        Setelah submit, pendaftaran <strong>tidak dapat dibatalkan</strong> dan akan langsung diproses 
                        oleh sistem verifikasi. Pastikan semua dokumen sudah benar.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Periksa Lagi</button>
                <button type="button" class="btn btn-danger" id="confirmSubmitBtn">Ya, Submit Sekarang</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Delete File -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h5 class="mb-3">Hapus file <span id="fileName"></span>?</h5>
                    <p class="text-muted">
                        File akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="fileToDelete" value="">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- If cannot apply or already submitted -->
<div class="dashboard-card">
    <div class="card-body text-center py-5">
        <?php if ($error): ?>
            <i class="fas fa-exclamation-circle fa-4x text-danger mb-4"></i>
            <h4 class="mb-3">Tidak Dapat Mendaftar</h4>
            <p class="text-muted mb-4"><?php echo $error; ?></p>
        <?php elseif ($success): ?>
            <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
            <h4 class="mb-3">Pendaftaran Berhasil!</h4>
            <p class="text-muted mb-4">Pendaftaran Anda telah berhasil dikirim dan akan diproses oleh tim verifikasi.</p>
        <?php endif; ?>
        
        <div class="d-flex justify-content-center gap-3">
            <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
            </a>
            <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="btn btn-outline-primary">
                <i class="fas fa-list me-2"></i>Lihat Status Pendaftaran
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    flex: 1;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 18px;
    left: 60%;
    width: 80%;
    height: 2px;
    background-color: #e9ecef;
    z-index: 1;
}

.step.active:not(:last-child)::after {
    background-color: #0d6efd;
}

.step-circle {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 5px;
    position: relative;
    z-index: 2;
    border: 2px solid white;
    font-size: 0.9rem;
}

.step.active .step-circle {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.step-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-align: center;
}

.step.active .step-label {
    color: #0d6efd;
    font-weight: 500;
}

.sticky-top {
    position: sticky;
    top: 20px;
}

.vacancy-details .detail-item {
    margin-bottom: 4px;
    padding: 3px 0;
    border-bottom: 1px dashed #e9ecef;
}

.vacancy-description {
    border-top: 1px solid #e9ecef;
    padding-top: 10px;
}

.vacancy-formations {
    border-top: 1px solid #e9ecef;
    padding-top: 10px;
}

.formation-item {
    padding: 3px 0;
}

.timeline-container {
    position: relative;
    padding-left: 25px;
    margin-top: 10px;
    border-top: 1px solid #e9ecef;
    padding-top: 10px;
}

.timeline-item {
    position: relative;
    padding-bottom: 15px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -16px;
    top: 8px;
    bottom: -15px;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item.active:not(:last-child)::before {
    background-color: #0d6efd;
}

.timeline-dot {
    position: absolute;
    left: -20px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #e9ecef;
    border: 2px solid white;
}

.timeline-item.active .timeline-dot {
    background-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.timeline-content h6 {
    font-size: 0.8rem;
    margin-bottom: 2px;
}

.file-upload-area {
    border: 1px dashed #adb5bd;
    border-radius: 4px;
    padding: 5px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
    background-color: white;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-upload-area:hover {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.02);
}

.file-upload-placeholder {
    pointer-events: none;
    color: #6c757d;
}

.file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.file-preview {
    display: none;
}

.file-preview.show {
    display: block;
}

.preview-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px;
    background-color: #f8f9fa;
    border-radius: 3px;
    margin-bottom: 5px;
    font-size: 0.8rem;
}

.preview-info {
    display: flex;
    align-items: center;
}

.preview-icon {
    width: 30px;
    height: 30px;
    background-color: #e9ecef;
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
    color: #495057;
    font-size: 0.8rem;
}

.file-requirements {
    font-size: 0.75rem;
}

.document-item {
    transition: all 0.3s;
    background-color: white !important;
}

.document-item:hover {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.01) !important;
}

.requirements-list .list-group-item {
    border: none;
    padding: 8px 0;
    background: none;
    font-size: 0.85rem;
}

.requirement-group:not(:last-child) {
    border-bottom: 1px dashed #e9ecef;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.existing-file-item {
    transition: all 0.3s;
    font-size: 0.85rem;
}

.existing-file-item:hover {
    background-color: #f8f9fa;
}

.alert-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
    border-radius: 0.2rem;
}

.toast {
    min-width: 200px;
    margin-bottom: 5px;
    font-size: 0.85rem;
}

@media (max-width: 992px) {
    .sticky-top {
        position: static;
    }
}

@media (max-width: 768px) {
    .progress-steps {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .step {
        flex-direction: row;
        width: 100%;
    }
    
    .step:not(:last-child)::after {
        display: none;
    }
    
    .step-circle {
        margin-right: 10px;
        margin-bottom: 0;
    }
    
    .step-label {
        text-align: left;
    }
    
    .file-upload-area {
        padding: 3px;
        min-height: 50px;
    }
}
</style>

<script>
    // Define BASE_URL for JavaScript
    var BASE_URL = "<?php echo rtrim(base_url(), '/') . '/'; ?>";
    console.log("BASE_URL:", BASE_URL);
</script>
<script src="<?php echo base_url('modules/submission/js-apply.js'); ?>"></script>

<?php
include $base_path . '/modules/dashboard/footer-dashboard.php';
?>