<?php
// modules/submission/auto-save-file.php

// Define base path
$base_path = dirname(__DIR__, 2);

// Load configuration FIRST
require_once $base_path . '/config/config.php';

// JANGAN panggil session_start() di sini
// Biarkan config.php atau header-dashboard.php yang mengatur session

// Check if session is active
if (session_status() === PHP_SESSION_NONE) {
    // Hanya start session jika belum dimulai
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    error_log("DEBUG: User not logged in or session expired. User ID: " . ($_SESSION['user_id'] ?? 'not set'));
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh the page.']);
    exit;
}

// Check user role
if ($_SESSION['user_role'] !== 'USER') {
    error_log("DEBUG: Invalid user role. Role: " . $_SESSION['user_role']);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Invalid user role']);
    exit;
}

// Load auth functions (contains validate_csrf_token)
require_once $base_path . '/modules/auth/functions-auth.php';
// Load submission functions
require_once __DIR__ . '/functions-submission.php';

// Set header JSON
header('Content-Type: application/json');

// Check if it's an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    error_log("DEBUG: Not an AJAX request");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request type']);
    exit;
}

// === NEW: Validasi CSRF token ===
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf_token)) {
    error_log("CSRF validation failed for auto-save. Token: $csrf_token");
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid. Refresh halaman.']);
    exit;
}

// Get parameters
$submission_id = intval($_POST['submission_id'] ?? 0);
$document_id = intval($_POST['document_id'] ?? 0);
$document_code = trim($_POST['document_code'] ?? '');
$document_number = trim($_POST['document_number'] ?? '');
$document_date = trim($_POST['document_date'] ?? '');
$action = trim($_POST['action'] ?? '');

error_log("DEBUG: Auto-save params - action: $action, submission_id: $submission_id, document_id: $document_id, document_code: $document_code, doc_number: $document_number, doc_date: $document_date");

// --- DELETE FILE (AJAX) ---
if ($action === 'delete_file') {
    $file_id = intval($_POST['file_id'] ?? 0);
    if ($submission_id <= 0 || $file_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parameter tidak valid']);
        exit;
    }

    $db = get_db_connection();
    $user_id = $_SESSION['user_id'];

    // Verify file belongs to user's submission (allow draft, submitted, rejected_satker)
    $sql = "SELECT sf.id, sf.file_path 
            FROM submission_files sf
            JOIN submissions s ON sf.submission_id = s.id
            WHERE sf.id = ? AND s.user_id = ? 
            AND s.status IN ('draft', 'submitted', 'rejected_satker')";
    $stmt = $db->prepare($sql);
    $stmt->execute([$file_id, $user_id]);
    $file = $stmt->fetch();

    if (!$file) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau tidak dapat dihapus']);
        exit;
    }

    // Delete physical file
    $full_path = $base_path . '/' . $file['file_path'];
    if (file_exists($full_path)) {
        unlink($full_path);
        error_log("DELETE_FILE_AJAX: Removed physical file: $full_path");
    }

    // Delete DB record
    $stmt = $db->prepare("DELETE FROM submission_files WHERE id = ?");
    $stmt->execute([$file_id]);

    // Update submission timestamp
    $stmt = $db->prepare("UPDATE submissions SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$submission_id]);

    log_activity('FILE_DELETED', "User deleted file ID: $file_id from submission ID: $submission_id", $user_id);

    echo json_encode(['success' => true, 'message' => 'File berhasil dihapus', 'file_id' => $file_id]);
    exit;
}

// --- METADATA-ONLY UPDATE ---
if ($action === 'update_metadata') {
    if ($submission_id <= 0 || $document_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    $db = get_db_connection();
    $user_id = $_SESSION['user_id'];

    // Verify submission belongs to user and is draft
    $sql = "SELECT id FROM submissions WHERE id = ? AND user_id = ? AND status = 'draft'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$submission_id, $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Draft tidak ditemukan']);
        exit;
    }

    // Get document_code from vacancy_documents
    $sql = "SELECT document_code FROM vacancy_documents WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$document_id]);
    $doc = $stmt->fetch();
    if (!$doc) {
        echo json_encode(['success' => true, 'message' => 'No document found, skipping']);
        exit;
    }

    // Update metadata on existing submission_files matching this document_code
    $sql = "UPDATE submission_files 
            SET document_number = ?, document_date = ?
            WHERE submission_id = ? 
            AND (file_name LIKE ? OR file_path LIKE ?)";
    $like_pattern = $doc['document_code'] . '_%';
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ($document_number !== '' ? $document_number : null),
        ($document_date !== '' ? $document_date : null),
        $submission_id,
        $like_pattern,
        $like_pattern,
    ]);

    // Also update timestamp
    $sql = "UPDATE submissions SET updated_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$submission_id]);

    echo json_encode(['success' => true, 'message' => 'Metadata diperbarui']);
    exit;
}

// --- FILE UPLOAD (original flow) ---
if ($submission_id <= 0 || $document_id <= 0 || empty($document_code)) {
    error_log("ERROR: Invalid parameters");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = get_db_connection();
$user_id = $_SESSION['user_id'];

// Verify submission belongs to user and is draft
$sql = "SELECT s.id, s.vacancy_id, s.status 
        FROM submissions s
        WHERE s.id = ? AND s.user_id = ? AND s.status = 'draft'";
$stmt = $db->prepare($sql);
$stmt->execute([$submission_id, $user_id]);
$submission = $stmt->fetch();

if (!$submission) {
    error_log("ERROR: Draft not found or not accessible. Submission ID: $submission_id, User ID: $user_id");
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Draft tidak ditemukan atau tidak dapat diakses']);
    exit;
}

// Get document details
$sql = "SELECT * FROM vacancy_documents WHERE id = ? AND vacancy_id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$document_id, $submission['vacancy_id']]);
$document = $stmt->fetch();

if (!$document) {
    error_log("ERROR: Document not found. Document ID: $document_id, Vacancy ID: " . $submission['vacancy_id']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_msg = 'Tidak ada file yang diunggah';
    if (isset($_FILES['file']['error'])) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (batas server)',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (batas form)',
            UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Ekstensi file tidak diizinkan'
        ];
        $error_msg = $upload_errors[$_FILES['file']['error']] ?? 'Upload error ' . $_FILES['file']['error'];
    }
    
    error_log("ERROR: File upload error: $error_msg");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error_msg]);
    exit;
}

// Validate file
$allowed_types = array_map('trim', explode(',', $document['file_types']));
$max_size = $document['max_size'];
$file_errors = validate_submission_file($_FILES['file'], $allowed_types, $max_size);

if (!empty($file_errors)) {
    error_log("ERROR: File validation failed: " . implode(', ', $file_errors));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $file_errors)]);
    exit;
}

// Delete existing file for this document if exists
$sql = "SELECT sf.id, sf.file_path 
        FROM submission_files sf 
        WHERE sf.submission_id = ? 
        AND sf.file_name LIKE ?";
$stmt = $db->prepare($sql);
$like_pattern = $document_code . '_%';
$stmt->execute([$submission_id, $like_pattern]);
$existing_file = $stmt->fetch();

if ($existing_file) {
    // Delete physical file
    $file_path = $base_path . '/' . $existing_file['file_path'];
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            error_log("WARNING: Failed to delete existing file: $file_path");
        }
    }
    
    // Delete database record
    $sql = "DELETE FROM submission_files WHERE id = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt->execute([$existing_file['id']])) {
        error_log("WARNING: Failed to delete file record from database");
    }
}

// Save new file
$saved_file = save_submission_file($_FILES['file'], $document_code, $submission_id, $document_number, $document_date, $document_id);

if (!$saved_file) {
    error_log("ERROR: Failed to save file to server");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file ke server']);
    exit;
}

// Save to database
$doc_id = intval($document_id);
$sql = "INSERT INTO submission_files (submission_id, file_name, file_path, file_type, file_size, mime_type, document_id, document_number, document_date, uploaded_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $db->prepare($sql);
$result = $stmt->execute([
    $submission_id,
    $saved_file['name'],
    $saved_file['path'],
    $saved_file['type'],
    $saved_file['size'],
    $saved_file['mime'],
    ($doc_id > 0 ? $doc_id : null),
    ($saved_file['document_number'] !== '' ? $saved_file['document_number'] : null),
    ($saved_file['document_date'] !== '' ? $saved_file['document_date'] : null),
]);

if (!$result) {
    error_log("ERROR: Failed to save file information to database: " . print_r($stmt->errorInfo(), true));
    
    // Try to delete the uploaded file
    $file_path = $base_path . '/' . $saved_file['path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan informasi file ke database']);
    exit;
}

$file_id = $db->lastInsertId();

// Update submission timestamp
$sql = "UPDATE submissions SET updated_at = NOW() WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$submission_id]);

// Log activity
log_activity('FILE_UPLOADED', "User uploaded file: {$saved_file['name']} for submission ID: $submission_id", $user_id);

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'File berhasil disimpan',
    'file_id' => $file_id,
    'file_name' => $saved_file['name'],
    'file_path' => base_url('modules/submission/view-file.php?file_id=' . $file_id . '&submission_id=' . $submission_id),
    'file_size' => $saved_file['size'],
    'file_size_formatted' => format_submission_bytes($saved_file['size']),
    'document_code' => $document_code,
    'document_id' => $document_id,
    'document_number' => $saved_file['document_number'] ?? '',
    'document_date' => $saved_file['document_date'] ?? '',
]);

error_log("SUCCESS: File uploaded successfully for submission ID: $submission_id, file: {$saved_file['name']}");
?>