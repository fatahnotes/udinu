<?php
// modules/submission/apply.php
ob_start(); // Prevent header errors from stray output

// Define base path for includes
$base_path = dirname(__DIR__, 2);

// Load configuration FIRST
require_once $base_path . '/config/config.php';

// Set page variables AFTER loading config
$pageTitle = "Pendaftaran Lowongan";
$activePage = "submission";
$customCSS = ''; // Loaded manually below
$customJS = '';  // Loaded manually below

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
$view_mode = isset($_GET['view']);
// is_edit is TRUE for new applications too (they get a draft immediately)
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

// Determine if this is editing a draft or viewing a submitted application
$resolved_submission_id = $submission_id ?: ($_POST['submission_id'] ?? 0);
if ($resolved_submission_id > 0) {
    $submission = get_submission_details($db, (int)$resolved_submission_id, $user_id);
    if ($submission) {
        $submission_id = (int)$resolved_submission_id;
        $vacancy_id = $submission['vacancy_id'];
        if ($submission['status'] === 'draft') {
            $is_edit = true;
        }
        // If already submitted, $show_form stays false → will show success page
    }
}

// ============================================================
// EDIT EXISTING SUBMISSION (draft or submitted)
// ============================================================
if ($is_edit && $submission_id > 0) {
    // Get submission details
    $submission = get_submission_details($db, $submission_id, $user_id);

    if (!$submission) {
        $error = 'Data pendaftaran tidak ditemukan.';
    } elseif (!in_array($submission['status'], ['draft', 'submitted', 'rejected_satker'])) {
        $error = 'Pendaftaran tidak dapat diedit. Status saat ini: ' . strtoupper($submission['status']);
    } elseif (in_array($submission['status'], ['submitted', 'rejected_satker']) && empty($submission['is_active'])) {
        $error = 'Pendaftaran sudah ditutup oleh admin.';
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
// ============================================================
// NEW APPLICATION
// ============================================================
elseif ($vacancy_id > 0) {
    // Check if user already has a non-draft submission for this vacancy
    $check_existing = $db->prepare("SELECT id, status FROM submissions WHERE user_id = ? AND vacancy_id = ? AND status != 'draft'");
    $check_existing->execute([$user_id, $vacancy_id]);
    $already_submitted = $check_existing->fetch();

    if ($already_submitted && !in_array($already_submitted['status'], ['rejected_satker'])) {
        // Already submitted (not rejected_satker) — load vacancy info for tracker display, skip form
        $vacancy_data = $db->prepare("SELECT v.*, vt.type_name FROM vacancies v LEFT JOIN vacancy_types vt ON v.vacancy_type_id=vt.id WHERE v.id = ?");
        $vacancy_data->execute([$vacancy_id]);
        $vacancy = $vacancy_data->fetch();
        $formations = get_vacancy_formations($db, $vacancy_id);
        $documents = get_vacancy_documents($db, $vacancy_id);
        $requirements = get_vacancy_requirements($db, $vacancy_id);
        $submission_id = $already_submitted['id'];
        $existing_files = get_submission_files($db, $submission_id);
        $show_form = false; // Show tracker, not form
        
        // Show flash message if exists (from redirect after submit)
        if (empty($error) && !empty($_SESSION['flash_success'])) {
            $success = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
    } elseif ($already_submitted && $already_submitted['status'] === 'rejected_satker') {
        // Rejected by satker — allow re-edit and re-submit
        $submission_id = $already_submitted['id'];
        $is_edit = true;
        
        // Load submission details and set up form
        $submission = get_submission_details($db, $submission_id, $user_id);
        $vacancy = $db->prepare("SELECT v.*, vt.type_name FROM vacancies v LEFT JOIN vacancy_types vt ON v.vacancy_type_id=vt.id WHERE v.id = ?");
        $vacancy->execute([$vacancy_id]);
        $vacancy = $vacancy->fetch();
        $formations = get_vacancy_formations($db, $vacancy_id);
        $documents = get_vacancy_documents($db, $vacancy_id);
        $requirements = get_vacancy_requirements($db, $vacancy_id);
        $existing_files = get_submission_files($db, $submission_id);
        $selected_formation_id = $submission['formation_id'] ?? 0;
        $show_form = true;
        
        // Reset status ke draft agar bisa disubmit ulang
        $db->prepare("UPDATE submissions SET status = 'draft', updated_at = NOW() WHERE id = ? AND user_id = ?")->execute([$submission_id, $user_id]);
    } else {
        // New application — check eligibility
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
                $is_edit = true; // Has existing draft — treat as edit
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
    } // close inner else block (new application eligibility)
}

// Handle POST: Cancel Draft (PROCESS BEFORE show_form check — cancel harus selalu bisa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_draft'])) {
    $cancel_submission_id = intval($_POST['submission_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $error = 'Token keamanan tidak valid.';
    } elseif ($cancel_submission_id <= 0) {
        $error = 'ID pendaftaran tidak valid.';
    } else {
        // Hapus submission dan semua file fisik
        $delete_result = cancel_submission_draft($db, $cancel_submission_id, $user_id);
        if ($delete_result) {
            $_SESSION['flash_success'] = 'Draft pendaftaran berhasil dihapus.';
            header('Location: ' . base_url('modules/submission/submission.php'));
            exit;
        } else {
            $error = 'Gagal membatalkan pendaftaran. Silakan coba lagi.';
        }
    }
}

// Handle POST: Delete single file from view mode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_view_file'])) {
    $file_id = intval($_POST['file_id'] ?? 0);
    $view_submission_id = intval($_POST['submission_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf_token($csrf_token)) {
        $_SESSION['flash_error'] = 'Token keamanan tidak valid.';
    } elseif ($file_id > 0 && $view_submission_id > 0) {
        // Verify file belongs to user's submission
        $check = $db->prepare("SELECT sf.id FROM submission_files sf JOIN submissions s ON sf.submission_id = s.id WHERE sf.id = ? AND s.user_id = ? AND s.status IN ('submitted', 'verified_satker', 'rejected_satker')");
        $check->execute([$file_id, $user_id]);
        if ($check->fetch()) {
            delete_submission_file($db, $file_id, $user_id);
            $_SESSION['flash_success'] = 'Dokumen berhasil dihapus.';
        } else {
            $_SESSION['flash_error'] = 'Dokumen tidak ditemukan atau tidak dapat dihapus.';
        }
    }
    // Redirect back to view mode
    $redirect_url = base_url('modules/submission/apply.php?submission_id=' . $view_submission_id . '&view=1');
    if (!headers_sent()) {
        header('Location: ' . $redirect_url);
        exit;
    }
    echo '<script>window.location.href="' . $redirect_url . '";</script>';
    exit;
}

// Handle form submission (form utama)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form && !isset($_POST['cancel_draft'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($csrf_token)) {
        $error = 'Token keamanan kadaluarsa. Silakan <a href="javascript:location.reload()" class="alert-link">refresh halaman</a> lalu coba lagi.';
        // JANGAN hapus token, biarkan user retry
        $auto_refresh = false;
    } elseif (isset($_POST['submit_application']) && $_POST['submit_application'] === '1') {
        error_log("SUBMIT: Processing submit_application for submission_id=$submission_id, user_id=$user_id");
        $formation_id = intval($_POST['formation_id'] ?? 0);

        // Validate terms checkboxes (not using HTML5 required)
        $agree_terms = !empty($_POST['agree_terms']);
        $agree_privacy = !empty($_POST['agree_privacy']);
        $agree_final = !empty($_POST['agree_final']);
        if (!$agree_terms) $error = 'Anda harus menyetujui pernyataan kebenaran dokumen.';
        elseif (!$agree_privacy) $error = 'Anda harus menyetujui Kebijakan Privasi dan Syarat Layanan.';
        elseif (!$agree_final) $error = 'Anda harus menyetujui bahwa pendaftaran tidak dapat dibatalkan.';

        if ($error) { /* skip */ }
        elseif ($formation_id <= 0 && !empty($formations)) {
            $error = 'Harap pilih formasi yang akan dilamar.';
        } else {
            // Always fetch existing files from DB
            $existing_files = get_submission_files($db, $submission_id);

            $has_errors = false;
            $files_data = [];

            foreach ($documents as $doc) {
                $field_name = 'document_' . $doc['id'];

                // Check if file already exists in DB (via auto-save)
                $has_existing = false;
                foreach ($existing_files as $f) {
                    if (strpos($f['file_path'], $doc['document_code']) !== false || strpos($f['file_name'], $doc['document_code']) !== false) {
                        $has_existing = true;
                        break;
                    }
                }

                // Check if new file is being uploaded via form
                $has_new_upload = isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK;

                if ($doc['is_required'] && !$has_existing && !$has_new_upload) {
                    $error .= "Dokumen <strong>{$doc['document_name']}</strong> wajib diunggah.<br>";
                    $has_errors = true;
                    continue;
                }

                // Process new file upload (from form, if any)
                if ($has_new_upload) {
                    $allowed_types = array_map('trim', explode(',', $doc['file_types']));
                    $max_size = $doc['max_size'];
                    $file_errors = validate_submission_file($_FILES[$field_name], $allowed_types, $max_size);

                    if (!empty($file_errors)) {
                        $error .= "Dokumen {$doc['document_name']}: " . implode(', ', $file_errors) . "<br>";
                        $has_errors = true;
                    } else {
                        $docNumber = trim($_POST['doc_number_' . $doc['id']] ?? '');
                        $docDate   = trim($_POST['doc_date_' . $doc['id']] ?? '');
                        $saved_file = save_submission_file($_FILES[$field_name], $doc['document_code'], $submission_id, $docNumber, $docDate, $doc['id']);
                        if ($saved_file) {
                            $files_data[] = $saved_file;
                        } else {
                            $error .= "Gagal menyimpan dokumen {$doc['document_name']}.<br>";
                            $has_errors = true;
                        }
                    }
                }
            }

            if (!$has_errors && empty($error)) {
                $total_existing = count($existing_files);

                if ($total_existing > 0 || !empty($files_data) || empty($documents)) {
                    // Update document metadata for existing files before submit
                    update_existing_file_metadata($db, $submission_id, $user_id, $documents, $_POST);

                    // Delete files marked for deletion
                    $delete_files = $_POST['delete_files'] ?? '';
                    if (!empty($delete_files)) {
                        $delete_ids = array_filter(explode(',', $delete_files));
                        foreach ($delete_ids as $fid) {
                            delete_submission_file($db, intval($fid), $user_id);
                        }
                    }

                    $submit_result = submit_application_with_formation($db, $submission_id, $user_id, $formation_id, $files_data);
                    if ($submit_result) {
                        $_SESSION['flash_success'] = 'Pendaftaran berhasil dikirim!';
                        log_activity('APPLICATION_SUBMITTED', "Submitted application for vacancy ID: $vacancy_id", $user_id);
                        // Redirect to VIEW mode
                        $redirect_url = base_url('modules/submission/apply.php?submission_id=' . $submission_id . '&view=1');
                        if (!headers_sent()) {
                            header('Location: ' . $redirect_url);
                            exit;
                        }
                        echo '<script>window.location.href="' . $redirect_url . '";</script>';
                        exit;
                    } else {
                        error_log("SUBMIT FAILED: submit_application_with_formation returned false for submission_id=$submission_id, user_id=$user_id, formation_id=$formation_id");
                        $error = 'Gagal mengirim pendaftaran. Pastikan semua dokumen wajib telah diunggah dan draft masih aktif. Silakan coba lagi.';
                    }
                } else {
                    $error = 'Harap unggah minimal satu dokumen.';
                }
            }
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
                    // Save file with document metadata
                    $docNumber = trim($_POST['doc_number_' . $doc['id']] ?? '');
                    $docDate   = trim($_POST['doc_date_' . $doc['id']] ?? '');
                    $saved_file = save_submission_file($_FILES[$field_name], $doc['document_code'], $submission_id, $docNumber, $docDate, $doc['id']);

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

        // Update draft
        $delete_files = isset($_POST['delete_files']) ? explode(',', $_POST['delete_files']) : [];
        $delete_files = array_filter($delete_files); // Remove empty values

        if (update_submission_draft_with_formation($db, $submission_id, $user_id, $formation_id, $files_data, $delete_files)) {
            // Also update document_number and document_date for existing files
            update_existing_file_metadata($db, $submission_id, $user_id, $documents, $_POST);
            $success = 'Draft berhasil disimpan!';
            error_log("DEBUG: Draft saved successfully");

            // Refresh existing files
            $existing_files = get_submission_files($db, $submission_id);
        } else {
            $error = 'Gagal menyimpan draft. Silakan coba lagi.';
            error_log("ERROR: Failed to save draft");
        }
    } elseif (isset($_POST['update_submitted'])) {
        // *** NEW: Update data untuk submission yang sudah disubmit (tanpa mengubah status) ***
        error_log("UPDATE_SUBMITTED: Processing for submission_id=$submission_id, user_id=$user_id");
        $formation_id = intval($_POST['formation_id'] ?? 0);
        $files_data = [];
        $error = '';

        // Validasi formasi jika ada
        if ($formation_id <= 0 && !empty($formations)) {
            $error = 'Harap pilih formasi.';
        }

        if (empty($error)) {
            // Proses file baru yang diunggah melalui form
            foreach ($documents as $doc) {
                $field_name = 'document_' . $doc['id'];
                if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $allowed_types = array_map('trim', explode(',', $doc['file_types']));
                    $max_size = $doc['max_size'];
                    $file_errors = validate_submission_file($_FILES[$field_name], $allowed_types, $max_size);
                    if (!empty($file_errors)) {
                        $error .= "Dokumen {$doc['document_name']}: " . implode(', ', $file_errors) . "<br>";
                        continue;
                    }
                    $docNumber = trim($_POST['doc_number_' . $doc['id']] ?? '');
                    $docDate   = trim($_POST['doc_date_' . $doc['id']] ?? '');
                    $saved_file = save_submission_file($_FILES[$field_name], $doc['document_code'], $submission_id, $docNumber, $docDate, $doc['id']);
                    if ($saved_file) {
                        $files_data[] = $saved_file;
                    } else {
                        $error .= "Gagal menyimpan dokumen {$doc['document_name']}.<br>";
                    }
                }
            }

            if (empty($error)) {
                // Update metadata dan hapus file yang ditandai
                update_existing_file_metadata($db, $submission_id, $user_id, $documents, $_POST);
                $delete_files = $_POST['delete_files'] ?? '';
                if (!empty($delete_files)) {
                    $delete_ids = array_filter(explode(',', $delete_files));
                    foreach ($delete_ids as $fid) {
                        delete_submission_file($db, intval($fid), $user_id);
                    }
                }

                if (update_submitted_submission($db, $submission_id, $user_id, $formation_id, $files_data)) {
                    $_SESSION['flash_success'] = 'Perubahan berhasil disimpan.';
                    $redirect_url = base_url('modules/submission/apply.php?submission_id=' . $submission_id . '&view=1');
                    if (!headers_sent()) {
                        header('Location: ' . $redirect_url);
                        exit;
                    }
                    echo '<script>window.location.href="' . $redirect_url . '";</script>';
                    exit;
                } else {
                    $error = 'Gagal menyimpan perubahan.';
                    error_log("UPDATE_SUBMITTED FAILED for submission_id=$submission_id");
                }
            }
        }
    }
}

// After all processing — if form should show but submission is already submitted, force view
if ($submission_id > 0 && $show_form) {
    $check_stmt = $db->prepare("SELECT status FROM submissions WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$submission_id, $user_id]);
    $current_status = $check_stmt->fetchColumn();
    if ($current_status && $current_status !== 'draft') {
        $show_form = false;
        if (empty($success)) {
            $success = 'Anda telah terdaftar pada ujian ini. Status: ' . strtoupper($current_status);
        }
    }
}

// VIEW MODE: force read-only (prinsip verifikasi_detail.php?view=1)
if ($view_mode && $submission_id > 0) {
    $show_form = false;
    // Read flash messages
    if (empty($success) && !empty($_SESSION['flash_success'])) {
        $success = $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
    }
    if (empty($error) && !empty($_SESSION['flash_error'])) {
        $error = $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
    }
}

// Determine form status for conditional display (used in banner + form)
$form_status = 'draft';
if ($submission_id > 0) {
    $fs = $db->prepare("SELECT status FROM submissions WHERE id = ? AND user_id = ?");
    $fs->execute([$submission_id, $user_id]);
    $form_status = $fs->fetchColumn() ?: 'draft';
}

// *** NEW: Untuk membedakan mode edit submitted ***
$edit_mode = $is_edit && $form_status === 'submitted' && $show_form;

// Load header dashboard
include $base_path . '/modules/dashboard/header-dashboard.php';
?>

<!-- Custom CSS for apply page -->
<link rel="stylesheet" href="<?php echo base_url('modules/submission/css-apply.css'); ?>">

<div class="dash-banner mb-4">
    <div class="dash-banner-bg" style="height:80px"></div>
    <div class="dash-banner-content" style="margin-top:-35px">
        <div class="dash-banner-text">
            <h2 class="dash-banner-title"><?php echo $view_mode ? 'Detail Pendaftaran' : (($form_status === 'draft') ? 'Pendaftaran Ujian' : (($edit_mode) ? 'Edit Pendaftaran' : 'Pendaftaran Disubmit')); ?></h2>
            <p class="dash-banner-sub"><?php echo $view_mode ? 'Data pendaftaran Anda telah berhasil dikirim' : (($form_status === 'draft') ? 'Lengkapi formulir dan unggah dokumen persyaratan' : (($edit_mode) ? 'Edit data pendaftaran yang sudah dikirim' : 'Pendaftaran Anda telah berhasil dikirim dan sedang diproses')); ?></p>
        </div>
        <div class="ms-auto d-flex gap-2">
            <a href="<?php echo base_url('modules/submission/submission.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fas fa-list me-1"></i>Riwayat</a>
            <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
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

<?php if (!empty($auto_refresh)): ?>
<meta http-equiv="refresh" content="2;url=?id=<?php echo $vacancy_id; ?>&submission_id=<?php echo $submission_id; ?>">
<?php endif; ?>

<?php
// Detailed progress tracker for existing submissions (beyond draft)
$show_tracker = false;
$tracker_stages_detailed = [];
if ($submission_id > 0) {
    $sub = get_submission_details($db, $submission_id, $user_id);
    if ($sub && $sub['status'] !== 'draft') {
        $show_tracker = true;
        $tStatus = strtolower($sub['status'] ?? 'draft');
        
        // Map 7-stage status to progress index
        $status_to_index = [
            'submitted' => 1,
            'verified_satker' => 2, 'rejected_satker' => 1,
            'verified_pusat' => 3, 'rejected_pusat' => 2,
            'exam_phase' => 4,
            'scoring_phase' => 5,
            'announced' => 6,
            'certified' => 7, 'passed' => 7, 'not_passed' => 6,
        ];
        $tCurrent = $status_to_index[$tStatus] ?? 0;
        $is_rejected = in_array($tStatus, ['rejected_satker', 'rejected_pusat', 'not_passed']);
        
        $tracker_stages_detailed = [
            ['icon'=>'fa-file-alt',   'label'=>'Pendaftaran',       'date'=>$sub['submission_date']??null,      'notes'=>'Dokumen telah disubmit',          'done'=>$tCurrent>=1, 'active'=>$tCurrent===1],
            ['icon'=>'fa-building',   'label'=>'Verifikasi Satker', 'date'=>$sub['satker_verified_at']??null,   'notes'=>'Diperiksa oleh admin satker',     'done'=>!$is_rejected && $tCurrent>=2, 'active'=>!$is_rejected && $tCurrent===2],
            ['icon'=>'fa-landmark',   'label'=>'Verifikasi Pusat',  'date'=>$sub['pusat_verified_at']??null,    'notes'=>'Diperiksa oleh admin pusat',      'done'=>!$is_rejected && $tCurrent>=3, 'active'=>!$is_rejected && $tCurrent===3],
            ['icon'=>'fa-pencil-alt', 'label'=>'Masa Ujian',        'date'=>null,                                'notes'=>'Mengikuti ujian (CAT/Makalah)',   'done'=>!$is_rejected && $tCurrent>=4, 'active'=>!$is_rejected && $tCurrent===4],
            ['icon'=>'fa-star',       'label'=>'Masa Penilaian',    'date'=>$sub['scoring_date']??null,         'notes'=>'Pemeriksaan dan penilaian',       'done'=>!$is_rejected && $tCurrent>=5, 'active'=>!$is_rejected && $tCurrent===5],
            ['icon'=>'fa-bullhorn',   'label'=>'Pengumuman',        'date'=>$sub['announcement_date']??null,    'notes'=>'Hasil kelulusan',                 'done'=>!$is_rejected && $tCurrent>=6, 'active'=>!$is_rejected && $tCurrent===6],
            ['icon'=>'fa-certificate','label'=>'Sertifikat',        'date'=>null,                                'notes'=>'Unduh sertifikat kelulusan',      'done'=>($tStatus==='certified'||$tStatus==='passed'), 'active'=>false],
        ];
        
        // Handle rejected states — mark rejection
        if ($tStatus === 'rejected_satker') {
            $tracker_stages_detailed[1]['done'] = false;
            $tracker_stages_detailed[1]['notes'] = 'Ditolak: ' . ($sub['satker_notes'] ?? 'Berkas tidak lengkap');
        }
        if ($tStatus === 'rejected_pusat') {
            $tracker_stages_detailed[2]['done'] = false;
            $tracker_stages_detailed[2]['notes'] = 'Ditolak: ' . ($sub['pusat_notes'] ?? 'Tidak memenuhi syarat');
        }
        if ($tStatus === 'not_passed') {
            $tracker_stages_detailed[5]['done'] = true;
            $tracker_stages_detailed[5]['notes'] = 'Tidak lulus — coba lagi';
        }
    }
}
?>

<?php if ($show_tracker): ?>
<!-- ============================================================ -->
<!-- DETAILED PROGRESS TRACKER -->
<!-- ============================================================ -->
<div class="tracker-card mb-4">
    <div class="tracker-title"><i class="fas fa-route me-2"></i>Status Pendaftaran: <strong><?php echo htmlspecialchars($sub['vacancy_title'] ?? ''); ?></strong> <span class="badge rounded-pill ms-2" style="background:#eef2ff;color:#4f46e5;font-size:0.7rem"><?php echo htmlspecialchars($sub['type_name']??''); ?></span></div>
    <div class="tracker-detail-list">
        <?php foreach ($tracker_stages_detailed as $i => $st): ?>
        <div class="tracker-detail-row <?php echo $st['done']?'tracker-done':($st['active']?'tracker-active':''); ?>">
            <div class="tracker-detail-marker">
                <div class="tracker-detail-circle"><i class="fas <?php echo $st['icon']; ?>"></i></div>
                <?php if ($i < 6): ?><div class="tracker-detail-line"></div><?php endif; ?>
            </div>
            <div class="tracker-detail-content">
                <div class="tracker-detail-label"><?php echo $st['label']; ?></div>
                <div class="tracker-detail-desc"><?php echo $st['notes']; ?></div>
                <?php if (!empty($st['date'])): ?>
                <div class="tracker-detail-date"><i class="fas fa-calendar-check me-1"></i><?php echo date('d M Y H:i', strtotime($st['date'])); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($tStatus === 'passed' || $tStatus === 'certified'): ?>
    <div class="tracker-footer text-center mt-3 pt-3 border-top">
        <span class="badge bg-success rounded-pill px-3 py-2 me-2"><i class="fas fa-check-circle me-1"></i>SELAMAT ANDA LULUS!</span>
        <button class="btn btn-primary btn-sm rounded-pill px-4" onclick="alert('Fitur download sertifikat akan segera tersedia.')"><i class="fas fa-download me-1"></i>Download Sertifikat</button>
    </div>
    <?php elseif ($tStatus === 'not_passed'): ?>
    <div class="tracker-footer text-center mt-3 pt-3 border-top">
        <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fas fa-times-circle me-1"></i>Belum Lulus — Tetap semangat dan coba lagi!</span>
    </div>
    <?php elseif (strpos($tStatus, 'rejected') !== false): ?>
    <div class="tracker-footer text-center mt-3 pt-3 border-top">
        <span class="badge bg-warning rounded-pill px-3 py-2"><i class="fas fa-exclamation-triangle me-1"></i>Ditolak — Silakan periksa catatan verifikasi.</span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($show_form): ?>
<!-- Apply Form -->
<div class="apply-container">
    <!-- Progress Steps -->
    <div class="apply-steps mb-4">
        <div class="apply-step done"><div class="apply-step-num"><i class="fas fa-check"></i></div><span>Info Ujian</span></div>
        <div class="apply-step active"><div class="apply-step-num">2</div><span>Unggah Dokumen</span></div>
        <div class="apply-step"><div class="apply-step-num">3</div><span>Konfirmasi</span></div>
    </div>

    <div class="row">
        <!-- Left Column: Vacancy Info -->
        <div class="col-lg-4 mb-4">
            <div class="dash-section-card">
                <div class="dash-section-header"><i class="fas fa-info-circle text-blue me-2"></i>Informasi Ujian</div>
                <?php if ($vacancy): ?>
                    <h6 class="fw-bold mb-3" style="color:#1e293b"><?php echo htmlspecialchars($vacancy['title']); ?></h6>
                    <div class="dash-sidebar-list">
                        <div class="dash-sidebar-item"><span>Kode</span><span class="fw-semibold"><?php echo htmlspecialchars($vacancy['vacancy_code']); ?></span></div>
                        <div class="dash-sidebar-item"><span>Jenis</span><span class="badge rounded-pill" style="background:#eef2ff;color:#4f46e5;font-size:0.7rem"><?php echo htmlspecialchars($vacancy['type_name']??'N/A'); ?></span></div>
                        <div class="dash-sidebar-item"><span>Buka</span><span class="text-muted small"><?php echo date('d M Y', strtotime($vacancy['open_date'])); ?></span></div>
                        <div class="dash-sidebar-item"><span>Tutup</span><span class="text-muted small"><?php echo date('d M Y', strtotime($vacancy['close_date'])); ?></span></div>
                        <?php if ($vacancy['max_applicants']): ?>
                        <div class="dash-sidebar-item"><span>Kuota</span><span class="fw-semibold"><?php echo $vacancy['current_applicants']; ?>/<?php echo $vacancy['max_applicants']; ?></span></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($vacancy['description'])): ?>
                    <div class="mt-3 pt-2 border-top"><small class="text-muted"><?php echo nl2br(htmlspecialchars($vacancy['description'])); ?></small></div>
                    <?php endif; ?>
                    <?php if (!empty($formations)): ?>
                    <div class="mt-3 pt-2 border-top"><small class="fw-bold text-muted text-uppercase">Formasi</small>
                        <?php foreach ($formations as $f): ?>
                        <div class="small mt-1"><i class="fas fa-user-tie me-1 text-muted"></i><?php echo htmlspecialchars($f['formation_name']); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="alert alert-<?php echo ($form_status === 'draft') ? 'info' : 'success'; ?> small mt-3 mb-0 py-2 px-3 rounded-3">
                        <i class="fas fa-<?php echo ($form_status === 'draft') ? 'info-circle' : 'check-circle'; ?> me-1"></i>
                        <?php echo ($form_status === 'draft') ? 'Simpan draft untuk melanjutkan nanti.' : (($edit_mode) ? 'Anda sedang mengedit pendaftaran yang sudah dikirim.' : 'Pendaftaran telah disubmit. Anda masih dapat mengedit selama belum ditutup admin.'); ?>
                    </div>
                    <!-- Status Pendaftaran Indicator -->
                    <div class="mt-3 pt-2 border-top">
                        <small class="fw-bold text-muted text-uppercase">Status Pendaftaran</small>
                        <?php if ($form_status === 'draft'): ?>
                        <div class="d-flex align-items-center gap-2 mt-2 p-2 rounded-3" style="background:#fffbeb;border:1px solid #fde68a">
                            <i class="fas fa-edit text-warning"></i>
                            <div>
                                <span class="fw-semibold text-warning" style="font-size:0.82rem">Draft</span>
                                <div class="text-muted" style="font-size:0.72rem">Lengkapi dokumen & submit</div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="d-flex align-items-center gap-2 mt-2 p-2 rounded-3" style="background:#ecfdf5;border:1px solid #6ee7b7">
                            <i class="fas fa-check-circle text-success"></i>
                            <div>
                                <span class="fw-semibold text-success" style="font-size:0.82rem"><?php echo $edit_mode ? 'Editing' : 'Disubmit'; ?></span>
                                <div class="text-muted" style="font-size:0.72rem"><?php echo $edit_mode ? 'Simpan perubahan untuk memperbarui' : 'Pendaftaran berhasil dikirim'; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Informasi tidak tersedia.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Unified Form -->
        <div class="col-lg-8">
            <form method="POST" action="" enctype="multipart/form-data" id="applyForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                <input type="hidden" name="delete_files" id="deleteFiles" value="">
                <!-- Hidden input for programmatic submit — JS sets value='1' before form.submit() -->
                <input type="hidden" name="submit_application" id="hiddenSubmitApp" value="">

                <!-- Formation -->
                <?php if (!empty($formations)): ?>
                <div class="dash-section-card">
                    <div class="dash-section-header"><i class="fas fa-user-tie text-indigo me-2"></i>Pilih Formasi</div>
                    <select class="form-select form-select-sm" name="formation_id" id="formation_id">
                        <option value="">-- Pilih Formasi --</option>
                        <?php foreach ($formations as $formation): ?>
                        <option value="<?php echo $formation['id']; ?>" <?php echo $selected_formation_id==$formation['id']?'selected':''; ?>><?php echo htmlspecialchars($formation['formation_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Unified: Persyaratan + Dokumen -->
                <div class="dash-section-card">
                    <div class="dash-section-header"><i class="fas fa-file-upload text-blue me-2"></i>Dokumen Persyaratan <span class="text-muted fw-normal small ms-2">— unggah satu per satu, tersimpan otomatis</span></div>
                    <?php if (empty($documents)): ?>
                        <p class="text-muted small mb-0 text-center py-3">Tidak ada dokumen yang diperlukan.</p>
                    <?php else: ?>
                        <?php foreach ($documents as $index => $doc):
                            $field_name = 'document_'.$doc['id'];
                            $allowed_types = explode(',', $doc['file_types']);
                            $max_size_mb = $doc['max_size'] / 1048576;

                            // Find matching requirement text
                            $matching_req = '';
                            foreach ($requirements as $req) {
                                if (stripos($req['requirement_text'], $doc['document_name']) !== false || stripos($doc['document_name'], $req['requirement_text']) !== false) {
                                    $matching_req = $req['requirement_text']; break;
                                }
                            }

                            // Check existing file
                            $has_existing = false; $existing_link = ''; $existing_name = ''; $existing_id = 0;
                            $existing_doc_number = ''; $existing_doc_date = '';
                            if ($submission_id > 0) {
                                // Always check DB for auto-saved files
                                if (empty($existing_files)) {
                                    $existing_files = get_submission_files($db, $submission_id);
                                }
                                foreach ($existing_files as $f) {
                                    if (strpos($f['file_path'], $doc['document_code']) !== false || strpos($f['file_name'], $doc['document_code']) !== false) {
                                        $has_existing = true; $existing_link = base_url($f['file_path']);
                                        $existing_name = $f['file_name']; $existing_id = $f['id'];
                                        $existing_doc_number = $f['document_number'] ?? '';
                                        $existing_doc_date = $f['document_date'] ?? '';
                                        break;
                                    }
                                }
                            }
                        ?>
                        <div class="apply-doc-card <?php echo $has_existing?'doc-uploaded':''; ?>" id="docCard_<?php echo $doc['id']; ?>" data-doc-code="<?php echo $doc['document_code']; ?>" data-max-size="<?php echo $doc['max_size']; ?>" data-submission-id="<?php echo $submission_id; ?>" data-accept="<?php echo '.'.implode(',.',$allowed_types); ?>">
                            <div class="row align-items-start g-3">
                                <div class="col-md-7">
                                    <div class="d-flex align-items-start gap-2 mb-2">
                                        <span class="apply-doc-num"><?php echo $index+1; ?></span>
                                        <div>
                                            <div class="fw-semibold" style="font-size:0.88rem"><?php echo htmlspecialchars($doc['document_name']); ?><?php if($doc['is_required']):?> <span class="text-danger">*</span><?php endif; ?></div>
                                            <?php if (!empty($matching_req)): ?>
                                            <div class="text-muted mt-1" style="font-size:0.75rem;line-height:1.4"><i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars($matching_req); ?></div>
                                            <?php endif; ?>
                                            <div class="mt-1" style="font-size:0.7rem;color:#94a3b8">Format: <?php echo strtoupper(implode(', ',$allowed_types)); ?> &middot; Maks <?php echo number_format($max_size_mb,1); ?>MB</div>
                                            <div class="row g-2 mt-2">
                                                <div class="col-sm-6">
                                                    <label class="small text-muted mb-1" style="font-size:0.72rem">Nomor Dokumen</label>
                                                    <input type="text" class="form-control form-control-sm doc-number-input" name="doc_number_<?php echo $doc['id']; ?>" id="docNumber_<?php echo $doc['id']; ?>" placeholder="Contoh: 123/ABC/2025" value="<?php echo htmlspecialchars($existing_doc_number); ?>" style="font-size:0.78rem">
                                                </div>
                                                <div class="col-sm-6">
                                                    <label class="small text-muted mb-1" style="font-size:0.72rem">Tanggal Dokumen</label>
                                                    <input type="date" class="form-control form-control-sm doc-date-input" name="doc_date_<?php echo $doc['id']; ?>" id="docDate_<?php echo $doc['id']; ?>" value="<?php echo htmlspecialchars($existing_doc_date); ?>" style="font-size:0.78rem">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="apply-upload-zone" id="uploadZone_<?php echo $doc['id']; ?>">
                                        <?php if ($has_existing): ?>
                                            <div class="apply-upload-done">
                                                <i class="fas fa-check-circle"></i>
                                                <span class="apply-upload-filename" title="<?php echo htmlspecialchars($existing_name); ?>"><?php echo htmlspecialchars($existing_name); ?></span>
                                                <div class="apply-upload-actions">
                                                    <a href="<?php echo base_url('modules/submission/view-file.php?file_id=' . $existing_id . '&submission_id=' . $submission_id); ?>" target="_blank" class="apply-btn-view" title="Lihat dokumen"><i class="fas fa-eye"></i></a>
                                                    <button type="button" class="apply-btn-delete" title="Hapus" data-file-id="<?php echo $existing_id; ?>" data-doc-id="<?php echo $doc['id']; ?>" data-file-name="<?php echo htmlspecialchars($existing_name); ?>" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="apply-upload-empty">
                                                <i class="fas fa-cloud-upload-alt" style="font-size:1.5rem;color:#cbd5e1"></i>
                                                <span class="small text-muted">Pilih file...</span>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file"
                                               name="<?php echo $field_name; ?>"
                                               id="docFile_<?php echo $doc['id']; ?>"
                                               class="apply-file-input"
                                               accept="<?php echo '.'.implode(',.',$allowed_types); ?>"
                                               data-doc-id="<?php echo $doc['id']; ?>"
                                               data-doc-code="<?php echo $doc['document_code']; ?>"
                                               data-max-size="<?php echo $doc['max_size']; ?>"
                                               data-submission-id="<?php echo $submission_id; ?>"
                                               title="<?php echo $has_existing ? htmlspecialchars($existing_name) : ''; ?>"
                                               style="<?php echo $has_existing ? 'pointer-events: none;' : ''; ?>">
                                    </div>
                                    <div class="apply-upload-status small text-center mt-1" id="uploadStatus_<?php echo $doc['id']; ?>"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Terms -->
                <div class="dash-section-card">
                    <div class="dash-section-header"><i class="fas fa-file-contract text-amber me-2"></i>Pernyataan & Ketentuan</div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" value="1" <?php echo ($form_status === 'submitted') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="agreeTerms">Saya menyatakan semua dokumen yang saya berikan adalah <strong>benar dan sah</strong>.</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="agreePrivacy" name="agree_privacy" value="1" <?php echo ($form_status === 'submitted') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="agreePrivacy">Saya setuju dengan <strong>Kebijakan Privasi</strong> dan <strong>Syarat Layanan</strong> yang berlaku.</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeFinal" name="agree_final" value="1" <?php echo ($form_status === 'submitted') ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="agreeFinal">Saya memahami setelah submit, <strong>pendaftaran tidak dapat dibatalkan</strong> dan akan diproses sesuai alur seleksi.</label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="dash-section-card">
                    <?php if ($form_status === 'draft'): ?>
                    <!-- DRAFT MODE: show Save Draft + Cancel + Submit -->
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <i class="fas fa-times me-1"></i>Batalkan
                            </button>
                            <button type="submit" name="save_draft" class="btn btn-outline-secondary btn-sm rounded-pill">
                                <i class="fas fa-save me-1"></i>Simpan Draft
                            </button>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Dashboard</a>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#confirmSubmitModal" id="btnSubmitTop">
                                <i class="fas fa-paper-plane me-1"></i>Submit Pendaftaran
                            </button>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i>Setiap file <strong>tersimpan otomatis</strong> saat dipilih. Data aman jika koneksi terputus.</small>
                    </div>
                    <?php elseif ($edit_mode): ?>
                    <!-- EDIT SUBMITTED MODE: Simpan Perubahan + Batal -->
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" name="update_submitted" class="btn btn-primary btn-sm rounded-pill px-4">
                            <i class="fas fa-save me-1"></i>Simpan Perubahan
                        </button>
                        <a href="<?php echo base_url('modules/submission/apply.php?submission_id=' . $submission_id . '&view=1'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Batal</a>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Perubahan yang disimpan akan langsung tercatat tanpa mengubah status pengajuan.</small>
                    </div>
                    <?php else: ?>
                    <!-- VIEW MODE: tampilkan tombol Edit (jika belum ditutup admin) -->
                    <div class="d-flex justify-content-center flex-wrap gap-2">
                        <a href="<?php echo base_url('modules/submission/apply.php?submission_id=' . $submission_id . '&edit=1'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                            <i class="fas fa-edit me-1"></i>Edit Data Pendaftaran
                        </a>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-success"><i class="fas fa-check-circle me-1"></i>Pendaftaran telah dikirim. Status: <strong>Disubmit</strong>. Anda masih dapat mengedit selama belum ditutup admin.</small>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal (di LUAR main form agar tidak nested) -->
<form method="POST" action="" id="cancelDraftForm">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
    <input type="hidden" name="cancel_draft" value="1">
</form>
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px">
            <div class="modal-body text-center p-4">
                <div class="mb-3"><i class="fas fa-exclamation-triangle fa-3x text-danger"></i></div>
                <h6>Batalkan Pendaftaran?</h6>
                <p class="text-muted small">Semua data dan dokumen yang telah diunggah akan <strong>dihapus permanen</strong> dan tidak dapat dikembalikan.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-4" onclick="document.getElementById('cancelDraftForm').submit();"><i class="fas fa-trash me-1"></i>Ya, Batalkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submit Confirmation Modal -->
<div class="modal fade" id="confirmSubmitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px">
            <div class="modal-body text-center p-4">
                <div class="mb-3"><i class="fas fa-paper-plane fa-3x text-primary"></i></div>
                <h6>Konfirmasi Submit Pendaftaran</h6>
                <p class="text-muted small">Setelah submit, pendaftaran <strong>tidak dapat dibatalkan</strong> dan akan langsung diproses oleh sistem verifikasi.</p>
                <div class="apply-submit-summary text-start small bg-light rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between mb-1"><span>Ujian</span><strong><?php echo htmlspecialchars($vacancy['title']??''); ?></strong></div>
                    <div class="d-flex justify-content-between mb-1"><span>Kode</span><span><?php echo htmlspecialchars($vacancy['vacancy_code']??''); ?></span></div>
                    <div class="d-flex justify-content-between"><span>Dokumen</span><span><?php echo count($documents); ?> file</span></div>
                </div>
                <div class="form-check text-start mb-3">
                    <input class="form-check-input" type="checkbox" id="modalAgreeFinal">
                    <label class="form-check-label small" for="modalAgreeFinal">Saya yakin semua data dan dokumen sudah <strong>benar dan lengkap</strong>.</label>
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Periksa Lagi</button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" id="confirmSubmitBtn" disabled><i class="fas fa-paper-plane me-1"></i>Ya, Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete File Confirmation Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px">
            <div class="modal-body text-center p-4">
                <div class="mb-3"><i class="fas fa-trash-alt fa-3x text-danger"></i></div>
                <h6>Hapus File?</h6>
                <p class="text-muted small">File <strong id="deleteFileName">-</strong> akan dihapus secara permanen.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-4" id="confirmDeleteBtn"><i class="fas fa-trash me-1"></i>Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Already submitted or cannot apply -->
<?php
// Check if user has an existing submission
$existing_sub = null;
if ($vacancy_id > 0 || $submission_id > 0) {
    $lookup_id = $vacancy_id > 0 ? $vacancy_id : null;
    if ($submission_id > 0) {
        $stmt = $db->prepare("SELECT s.*, v.title as vacancy_title, vt.type_name FROM submissions s JOIN vacancies v ON s.vacancy_id=v.id JOIN vacancy_types vt ON v.vacancy_type_id=vt.id WHERE s.id=? AND s.user_id=? AND s.status != 'draft'");
        $stmt->execute([$submission_id, $user_id]);
    } else {
        $stmt = $db->prepare("SELECT s.*, v.title as vacancy_title, vt.type_name FROM submissions s JOIN vacancies v ON s.vacancy_id=v.id JOIN vacancy_types vt ON v.vacancy_type_id=vt.id WHERE s.user_id=? AND s.vacancy_id=? AND s.status != 'draft'");
        $stmt->execute([$user_id, $vacancy_id]);
    }
    $existing_sub = $stmt->fetch();
}
?>

<?php if ($existing_sub): ?>
<!-- ============================================================ -->
<!-- SUCCESS: READ-ONLY SUBMISSION SUMMARY -->
<!-- ============================================================ -->
<div class="apply-container">
    <!-- Success Banner -->
    <div class="dash-section-card text-center mb-4" style="border:2px solid #d1fae5">
        <div class="mb-2"><i class="fas fa-check-circle fa-3x text-success"></i></div>
        <h5 class="fw-bold text-success mb-1">Pendaftaran Berhasil Dikirim!</h5>
        <p class="text-muted small mb-0">Status: <span class="badge bg-success rounded-pill">Disubmit</span> &middot; Dikirim: <?php echo date('d M Y H:i', strtotime($existing_sub['submission_date'])); ?></p>
    </div>

    <div class="row">
        <!-- Left: Info Ujian (read-only) -->
        <div class="col-lg-4 mb-4">
            <div class="dash-section-card">
                <div class="dash-section-header"><i class="fas fa-info-circle text-blue me-2"></i>Informasi Ujian</div>
                <h6 class="fw-bold mb-3" style="color:#1e293b"><?php echo htmlspecialchars($existing_sub['vacancy_title']); ?></h6>
                <div class="dash-sidebar-list">
                    <div class="dash-sidebar-item"><span>Jenis</span><span class="badge rounded-pill" style="background:#eef2ff;color:#4f46e5;font-size:0.7rem"><?php echo htmlspecialchars($existing_sub['type_name']??'N/A'); ?></span></div>
                    <div class="dash-sidebar-item"><span>Status</span><span class="fw-semibold text-success">Disubmit</span></div>
                    <div class="dash-sidebar-item"><span>Tgl Daftar</span><span class="text-muted small"><?php echo date('d M Y H:i', strtotime($existing_sub['submission_date'])); ?></span></div>
                    <?php
                    // Get vacancy info
                    $vacInfo = $db->prepare("SELECT vacancy_code, open_date, close_date FROM vacancies WHERE id = ?");
                    $vacInfo->execute([$existing_sub['vacancy_id']]);
                    $vi = $vacInfo->fetch();
                    if ($vi):
                    ?>
                    <div class="dash-sidebar-item"><span>Kode</span><span class="fw-semibold"><?php echo htmlspecialchars($vi['vacancy_code']); ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="mt-3 pt-2 border-top">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:#ecfdf5;border:1px solid #6ee7b7">
                        <i class="fas fa-check-circle text-success"></i>
                        <div><span class="fw-semibold text-success" style="font-size:0.82rem">Pendaftaran Disubmit</span>
                        <div class="text-muted" style="font-size:0.72rem">Anda masih dapat mengedit selama belum ditutup admin</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Dokumen Terlampir + Pernyataan (read-only) -->
        <div class="col-lg-8">
            <!-- Dokumen Terlampir -->
            <div class="dash-section-card">
                <div class="dash-section-header"><i class="fas fa-paperclip text-blue me-2"></i>Dokumen Terlampir</div>
                <?php
                $submitted_files = get_submission_files($db, $existing_sub['id']);
                if (!empty($submitted_files)):
                    // Load document definitions for labels
                    $submitted_docs = get_vacancy_documents($db, $existing_sub['vacancy_id']);
                    $docMap = [];
                    foreach ($submitted_docs as $dd) { $docMap[$dd['document_code']] = $dd; }
                ?>
                <?php foreach ($submitted_files as $sf):
                    $docCode = '';
                    foreach ($docMap as $code => $dd) {
                        if (strpos($sf['file_name'], $code) !== false || strpos($sf['file_path'], $code) !== false) {
                            $docCode = $code; break;
                        }
                    }
                    $docLabel = $docMap[$docCode]['document_name'] ?? $sf['file_name'];
                    $docNum = $sf['document_number'] ?? '';
                    $docDate = $sf['document_date'] ?? '';
                ?>
                <div class="py-3 border-bottom border-light" id="viewFileRow_<?php echo $sf['id']; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fw-semibold" style="font-size:0.85rem;color:#1e293b"><?php echo htmlspecialchars($docLabel); ?></span>
                            <?php if (!empty($docNum)): ?>
                            <span class="badge bg-light text-dark ms-2" style="font-size:0.68rem">No: <?php echo htmlspecialchars($docNum); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($docDate)): ?>
                            <span class="badge bg-light text-dark ms-1" style="font-size:0.68rem">Tgl: <?php echo date('d/m/Y', strtotime($docDate)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="small text-muted"><i class="fas fa-file me-1"></i><?php echo htmlspecialchars($sf['file_name']); ?></span>
                        <span class="small text-muted"><?php echo date('d M H:i', strtotime($sf['uploaded_at'])); ?></span>
                        <div class="ms-auto d-flex gap-2">
                            <a href="<?php echo base_url('modules/submission/view-file.php?file_id=' . $sf['id'] . '&submission_id=' . $existing_sub['id']); ?>" target="_blank" class="apply-btn-view" title="Lihat dokumen"><i class="fas fa-eye"></i></a>
                            <button type="button" class="apply-btn-delete" title="Hapus dokumen" data-file-id="<?php echo $sf['id']; ?>" data-file-name="<?php echo htmlspecialchars($sf['file_name']); ?>" data-submission-id="<?php echo $existing_sub['id']; ?>" onclick="deleteFileFromView(<?php echo $sf['id']; ?>, '<?php echo htmlspecialchars($sf['file_name'], ENT_QUOTES); ?>', <?php echo $existing_sub['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted small text-center py-3 mb-0">Tidak ada dokumen.</p>
                <?php endif; ?>
            </div>

            <!-- Pernyataan & Ketentuan (checked, read-only) -->
            <div class="dash-section-card">
                <div class="dash-section-header"><i class="fas fa-file-contract text-amber me-2"></i>Pernyataan & Ketentuan</div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" checked disabled>
                    <label class="form-check-label small text-success">Saya menyatakan semua dokumen yang saya berikan adalah <strong>benar dan sah</strong>.</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" checked disabled>
                    <label class="form-check-label small text-success">Saya setuju dengan <strong>Kebijakan Privasi</strong> dan <strong>Syarat Layanan</strong> yang berlaku.</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" checked disabled>
                    <label class="form-check-label small text-success">Saya memahami setelah submit, <strong>pendaftaran tidak dapat dibatalkan</strong> dan akan diproses sesuai alur seleksi.</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons: hanya 2 tombol — Kembali ke Dashboard & Edit -->
    <div class="d-flex justify-content-center gap-3 mt-3 mb-4">
        <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard</a>
        <a href="<?php echo base_url('modules/submission/apply.php?submission_id=' . $existing_sub['id'] . '&edit=1'); ?>" class="btn btn-outline-primary rounded-pill px-4"><i class="fas fa-edit me-2"></i>Edit Data Pendaftaran</a>
    </div>
</div>

<?php else: ?>
<!-- Cannot apply — show error -->
<div class="text-center py-5">
    <?php if ($error): ?>
        <i class="fas fa-exclamation-circle fa-4x text-danger mb-3 d-block"></i>
        <h4>Tidak Dapat Mendaftar</h4>
        <p class="text-muted"><?php echo $error; ?></p>
    <?php elseif ($success): ?>
        <i class="fas fa-check-circle fa-4x text-success mb-3 d-block"></i>
        <h4><?php echo htmlspecialchars($success); ?></h4>
    <?php endif; ?>
    <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn btn-primary btn-sm rounded-pill px-4 mt-2"><i class="fas fa-home me-1"></i>Dashboard</a>
</div>
<?php endif; ?>
<?php endif; ?>

<style>
/* === Apply Steps Indicator === */
.apply-steps {
    display: flex;
    background: #fff;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    gap: 0;
}
.apply-step {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    font-size: 0.82rem;
    font-weight: 500;
    color: #94a3b8;
    position: relative;
}
.apply-step:not(:last-child)::after {
    content: '';
    flex: 1;
    height: 2px;
    background: #e2e8f0;
    margin: 0 16px;
}
.apply-step.done { color: #059669; }
.apply-step.done::after { background: #059669; }
.apply-step.active { color: #1e293b; font-weight: 600; }
.apply-step-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f1f5f9;
    border: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}
.apply-step.done .apply-step-num { background: #d1fae5; border-color: #059669; color: #059669; }
.apply-step.active .apply-step-num { background: #1a3a5c; border-color: #1a3a5c; color: #fff; }

/* === Document Card === */
.apply-doc-card {
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s;
}
.apply-doc-card:last-child { border-bottom: 0; }
.apply-doc-card.doc-uploaded { background: #f8fdf9; margin: 0 -8px; padding: 16px 8px; border-radius: 10px; }
.apply-doc-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: #f1f5f9; color: #64748b;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
}
.apply-doc-card.doc-uploaded .apply-doc-num { background: #d1fae5; color: #059669; }

/* === Upload Zone (clean) === */
.apply-upload-zone {
    position: relative;
    border: 2px dashed #e2e8f0;
    border-radius: 10px;
    min-height: 74px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    cursor: pointer;
    overflow: hidden;
}
.apply-upload-zone:hover { border-color: #0d6efd; background: #f8faff; }
.apply-upload-zone.uploading { border-color: #f59e0b; background: #fffbeb; }
.apply-upload-empty {
    text-align: center; padding: 8px;
}
.apply-upload-empty i { font-size: 1.4rem; color: #cbd5e1; display: block; margin-bottom: 2px; }
.apply-upload-empty span { font-size: 0.72rem; color: #94a3b8; }

.apply-upload-done {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    width: 100%;
}
.apply-upload-done > i { font-size: 1.1rem; color: #059669; flex-shrink: 0; }
.apply-upload-filename {
    flex: 1;
    font-size: 0.72rem;
    font-weight: 500;
    color: #1e293b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.apply-upload-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
    position: relative;
    z-index: 5;
}
.apply-btn-view, .apply-btn-delete {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px; height: 28px;
    border-radius: 50%;
    border: 1px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    font-size: 0.7rem;
    transition: all 0.15s;
    text-decoration: none;
}
.apply-btn-view { color: #059669; }
.apply-btn-view:hover { background: #d1fae5; border-color: #059669; }
.apply-btn-delete { color: #ef4444; }
.apply-btn-delete:hover { background: #fee2e2; border-color: #ef4444; }
.apply-upload-status { min-height: 18px; font-size: 0.7rem; }
.apply-file-input {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; z-index: 1;
}

/* === Banner (matches dashboard/profile) === */
.dash-banner {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    background: #fff;
}
.dash-banner-bg {
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
    padding: 16px 28px 18px;
    margin-top: -35px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    background: #fff;
}
.dash-banner-text { flex: 1; min-width: 200px; }
.dash-banner-title { color: #1e293b; font-size: 1.2rem; font-weight: 700; margin-bottom: 2px; }
.dash-banner-sub { color: #64748b; font-size: 0.82rem; margin-bottom: 0; }

/* === Section Cards (matches dashboard) === */
.dash-section-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin-bottom: 16px;
}
.dash-section-header {
    font-size: 0.85rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
}
.dash-sidebar-list { display: flex; flex-direction: column; gap: 2px; }
.dash-sidebar-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 0.82rem;
    color: #334155;
}
.dash-sidebar-item + .dash-sidebar-item { border-top: 1px solid #f8fafc; }

/* === Modal Summary Box === */
.apply-submit-summary { font-size: 0.82rem; }
.apply-submit-summary .d-flex { padding: 3px 0; }
.apply-submit-summary .d-flex + .d-flex { border-top: 1px solid #e2e8f0; }

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

/* === Detailed Progress Tracker === */
.tracker-card {
    background: #fff;
    border-radius: 20px;
    padding: 24px 28px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.tracker-title {
    font-size: 0.88rem;
    color: #64748b;
    margin-bottom: 20px;
    text-align: center;
}
.tracker-detail-list { }
.tracker-detail-row {
    display: flex;
    gap: 16px;
    padding-bottom: 0;
    position: relative;
}
.tracker-detail-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 24px;
}
.tracker-detail-circle {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f1f5f9;
    border: 3px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    color: #94a3b8;
    z-index: 2;
    flex-shrink: 0;
    transition: all 0.3s;
}
.tracker-detail-line {
    width: 3px;
    flex: 1;
    min-height: 32px;
    background: #e2e8f0;
    margin: 4px 0;
}
.tracker-detail-content {
    flex: 1;
    padding-bottom: 20px;
    padding-top: 6px;
}
.tracker-detail-row:last-child .tracker-detail-content { padding-bottom: 0; }
.tracker-detail-row:last-child .tracker-detail-line { display: none; }
.tracker-detail-label {
    font-size: 0.88rem;
    font-weight: 700;
    color: #94a3b8;
    margin-bottom: 2px;
}
.tracker-detail-desc {
    font-size: 0.78rem;
    color: #cbd5e1;
    margin-bottom: 2px;
}
.tracker-detail-date {
    font-size: 0.72rem;
    color: #94a3b8;
}

/* Done */
.tracker-detail-row.tracker-done .tracker-detail-circle { background: #d1fae5; border-color: #059669; color: #059669; }
.tracker-detail-row.tracker-done .tracker-detail-line { background: #059669; }
.tracker-detail-row.tracker-done .tracker-detail-label { color: #059669; }
.tracker-detail-row.tracker-done .tracker-detail-desc { color: #6ee7b7; }
.tracker-detail-row.tracker-done .tracker-detail-date { color: #6ee7b7; }

/* Active */
.tracker-detail-row.tracker-active .tracker-detail-circle {
    background: #1a3a5c;
    border-color: #1a3a5c;
    color: #fff;
    box-shadow: 0 0 0 6px rgba(26,58,92,0.12);
    animation: trackerDetailPulse 2s infinite;
}
.tracker-detail-row.tracker-active .tracker-detail-label { color: #1e293b; }
.tracker-detail-row.tracker-active .tracker-detail-desc { color: #64748b; }
.tracker-detail-row.tracker-active .tracker-detail-date { color: #64748b; }
@keyframes trackerDetailPulse {
    0%,100% { box-shadow: 0 0 0 6px rgba(26,58,92,0.12); }
    50% { box-shadow: 0 0 0 14px rgba(26,58,92,0.04); }
}

/* === Document Metadata Inputs === */
.doc-number-input,
.doc-date-input {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    transition: border-color 0.2s;
}
.doc-number-input:focus,
.doc-date-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.08);
    background: #fff;
}
</style>

<!-- Hidden form untuk delete file di view mode -->
<form method="POST" action="" id="deleteViewFileForm" style="display:none">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="submission_id" id="deleteViewSubmissionId" value="">
    <input type="hidden" name="file_id" id="deleteViewFileId" value="">
    <input type="hidden" name="delete_view_file" value="1">
</form>

<!-- Delete File Confirmation Modal for View Mode -->
<div class="modal fade" id="confirmDeleteViewModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px">
            <div class="modal-body text-center p-4">
                <div class="mb-3"><i class="fas fa-trash-alt fa-3x text-danger"></i></div>
                <h6>Hapus Dokumen?</h6>
                <p class="text-muted small">File <strong id="deleteViewFileName">-</strong> akan dihapus secara permanen.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill px-4" id="confirmDeleteViewBtn"><i class="fas fa-trash me-1"></i>Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var BASE_URL = "<?php echo rtrim(base_url(), '/') . '/'; ?>";
    
    // Delete file from view mode (AJAX - no page reload)
    var pendingDeleteFileId = null;
    var pendingDeleteSubmissionId = null;
    var pendingDeleteDocId = null;
    
    function deleteFileFromView(fileId, fileName, submissionId) {
        pendingDeleteFileId = fileId;
        pendingDeleteSubmissionId = submissionId;
        document.getElementById('deleteViewFileName').textContent = fileName;
        var modal = new bootstrap.Modal(document.getElementById('confirmDeleteViewModal'));
        modal.show();
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        var confirmDeleteViewBtn = document.getElementById('confirmDeleteViewBtn');
        if (confirmDeleteViewBtn) {
            confirmDeleteViewBtn.addEventListener('click', function() {
                if (pendingDeleteFileId && pendingDeleteSubmissionId) {
                    // Hide modal first
                    var modalEl = document.getElementById('confirmDeleteViewModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    
                    // Send AJAX delete
                    var csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
                    if (!csrfToken) {
                        // Generate fresh token if not in form (view mode has hidden form)
                        csrfToken = document.querySelector('#deleteViewFileForm input[name="csrf_token"]')?.value;
                    }
                    
                    var fd = new FormData();
                    fd.append('csrf_token', csrfToken);
                    fd.append('action', 'delete_file');
                    fd.append('submission_id', pendingDeleteSubmissionId);
                    fd.append('file_id', pendingDeleteFileId);
                    
                    var row = document.getElementById('viewFileRow_' + pendingDeleteFileId);
                    if (row) row.style.opacity = '0.5';
                    
                    fetch(BASE_URL + 'modules/submission/auto-save-file.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            // Remove the row from view
                            if (row) row.style.display = 'none';
                            // Check if any files remain
                            var remaining = document.querySelectorAll('[id^="viewFileRow_"]:not([style*="display: none"])');
                            if (remaining.length === 0) {
                                // Reload page to show "Tidak ada dokumen" state
                                location.reload();
                            }
                        } else {
                            if (row) row.style.opacity = '1';
                            alert(d.message || 'Gagal menghapus file');
                        }
                    })
                    .catch(function(e) {
                        if (row) row.style.opacity = '1';
                        console.error('Delete error:', e);
                    });
                }
                pendingDeleteFileId = null;
                pendingDeleteSubmissionId = null;
            });
        }
    });
</script>
<script src="<?php echo base_url('modules/submission/js-apply.js'); ?>"></script>

<?php
include $base_path . '/modules/dashboard/footer-dashboard.php';
?>