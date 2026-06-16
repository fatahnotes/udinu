<?php
// modules/submission/functions-submission.php

/**
 * Get submission details by ID
 */
function get_submission_details($db, $submission_id, $user_id = null) {
    try {
        $sql = "SELECT s.*, v.title as vacancy_title, v.vacancy_code, 
                       v.vacancy_type_id, vt.type_name,
                       u.full_name as user_name, u.email as user_email,
                       v.open_date, v.close_date, v.is_active
                FROM submissions s
                JOIN vacancies v ON s.vacancy_id = v.id
                LEFT JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?";
        
        $params = [$submission_id];
        
        if ($user_id !== null) {
            $sql .= " AND s.user_id = ?";
            $params[] = $user_id;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting submission details: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has active submission
 */
function has_active_submission($db, $user_id) {
    try {
        $sql = "SELECT COUNT(*) 
                FROM submissions 
                WHERE user_id = ? 
                AND status NOT IN ('draft', 'rejected')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error checking active submission: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's current submission
 */
function get_user_current_submission($db, $user_id) {
    try {
        $sql = "SELECT s.*, v.title, v.vacancy_code, vt.type_name,
                       v.open_date, v.close_date, v.is_active
                FROM submissions s
                JOIN vacancies v ON s.vacancy_id = v.id
                LEFT JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
                WHERE s.user_id = ? 
                AND s.status NOT IN ('draft', 'rejected')
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting user submission: " . $e->getMessage());
        return false;
    }
}

/**
 * Get vacancy documents requirement
 */
function get_vacancy_documents($db, $vacancy_id) {
    try {
        $sql = "SELECT * FROM vacancy_documents 
                WHERE vacancy_id = ? 
                ORDER BY display_order, document_name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$vacancy_id]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting vacancy documents: " . $e->getMessage());
        return [];
    }
}

/**
 * Get submission files
 */
function get_submission_files($db, $submission_id) {
    try {
        $sql = "SELECT * FROM submission_files 
                WHERE submission_id = ? 
                ORDER BY uploaded_at";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$submission_id]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting submission files: " . $e->getMessage());
        return [];
    }
}

/**
 * Get vacancy requirements
 */
function get_vacancy_requirements($db, $vacancy_id) {
    try {
        $sql = "SELECT * FROM vacancy_requirements 
                WHERE vacancy_id = ? 
                ORDER BY requirement_type, display_order";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$vacancy_id]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting vacancy requirements: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user can apply to vacancy
 */
function can_apply_to_vacancy($db, $user_id, $vacancy_id) {
    try {
        // Check if vacancy exists and is open
        $sql = "SELECT v.*, vt.type_name,
                       (v.max_applicants IS NULL OR v.current_applicants < v.max_applicants) as has_quota
                FROM vacancies v
                LEFT JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
                WHERE v.id = ? 
                AND v.is_active = true
                AND v.open_date <= CURRENT_DATE
                AND v.close_date >= CURRENT_DATE";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$vacancy_id]);
        $vacancy = $stmt->fetch();
        
        if (!$vacancy) {
            return ['can_apply' => false, 'reason' => 'Lowongan tidak ditemukan atau tidak aktif'];
        }
        
        if (!$vacancy['has_quota']) {
            return ['can_apply' => false, 'reason' => 'Kuota pendaftar sudah penuh'];
        }
        
        // Check if user already applied to this vacancy
        // PERBAIKAN: Hapus alias 's.' karena tabel tidak diberi alias
        $sql = "SELECT id, status FROM submissions 
                WHERE user_id = ? AND vacancy_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $vacancy_id]);
        $existing_submission = $stmt->fetch();
        
        if ($existing_submission) {
            if ($existing_submission['status'] === 'draft') {
                return ['can_apply' => true, 'vacancy' => $vacancy, 'submission_id' => $existing_submission['id']];
            } else {
                return ['can_apply' => false, 'reason' => 'Anda sudah mendaftar lowongan ini'];
            }
        }
        
        // Check if user has other active submissions
        // PERBAIKAN: Hapus alias 's.' karena tabel tidak diberi alias
        $sql = "SELECT COUNT(*) FROM submissions 
                WHERE user_id = ? 
                AND status NOT IN ('draft', 'rejected')
                AND vacancy_id != ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $vacancy_id]);
        
        if ($stmt->fetchColumn() > 0) {
            return ['can_apply' => false, 'reason' => 'Anda hanya bisa mendaftar 1 lowongan aktif'];
        }
        
        // Check if user profile is complete
        $sql = "SELECT p.is_profile_complete FROM profiles p 
                WHERE p.user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        if (!$profile || !$profile['is_profile_complete']) {
            return ['can_apply' => false, 'reason' => 'Lengkapi profil Anda terlebih dahulu'];
        }
        
        return ['can_apply' => true, 'vacancy' => $vacancy];
        
    } catch (PDOException $e) {
        error_log("Error checking apply eligibility: " . $e->getMessage());
        return ['can_apply' => false, 'reason' => 'Terjadi kesalahan sistem'];
    }
}

/**
 * Create submission draft
 */
function create_submission_draft($db, $user_id, $vacancy_id) {
    try {
        // Check if draft already exists
        $sql = "SELECT id FROM submissions 
                WHERE user_id = ? AND vacancy_id = ? AND status = 'draft'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $vacancy_id]);
        
        if ($draft = $stmt->fetch()) {
            return $draft['id'];
        }
        
        // Create new draft
        $sql = "INSERT INTO submissions (user_id, vacancy_id, status, created_at) 
                VALUES (?, ?, 'draft', NOW()) 
                RETURNING id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $vacancy_id]);
        
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error creating submission draft: " . $e->getMessage());
        return false;
    }
}

/**
 * Submit application
 */
function submit_application($db, $submission_id, $user_id, $files_data) {
    $db->beginTransaction();
    
    try {
        // Update submission status
        $sql = "UPDATE submissions 
                SET status = 'submitted', 
                    submission_date = NOW(),
                    updated_at = NOW()
                WHERE id = ? AND user_id = ? AND status = 'draft'";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$submission_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Gagal mengupdate status pendaftaran");
        }
        
        // Insert submission files
        foreach ($files_data as $file) {
            $sql = "INSERT INTO submission_files 
                    (submission_id, file_name, file_path, file_type, 
                     file_size, mime_type, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $submission_id,
                $file['name'],
                $file['path'],
                $file['type'],
                $file['size'],
                $file['mime']
            ]);
        }
        
        // Log activity
        log_activity('SUBMISSION_SUBMITTED', "User submitted application ID: $submission_id", $user_id);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error submitting application: " . $e->getMessage());
        return false;
    }
}

/**
 * Cancel submission draft
 */
function cancel_submission_draft($db, $submission_id, $user_id) {
    try {
        $sql = "DELETE FROM submissions 
                WHERE id = ? AND user_id = ? AND status = 'draft'";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([$submission_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Error canceling submission draft: " . $e->getMessage());
        return false;
    }
}

/**
 * Update submission draft (edit)
 */
function update_submission_draft($db, $submission_id, $user_id, $files_data, $delete_files = []) {
    $db->beginTransaction();
    
    try {
        // Delete selected files
        if (!empty($delete_files)) {
            $placeholders = implode(',', array_fill(0, count($delete_files), '?'));
            $sql = "DELETE FROM submission_files 
                    WHERE id IN ($placeholders) AND submission_id = ?";
            
            $params = array_merge($delete_files, [$submission_id]);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Insert new files
        foreach ($files_data as $file) {
            $sql = "INSERT INTO submission_files 
                    (submission_id, file_name, file_path, file_type, 
                     file_size, mime_type, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $submission_id,
                $file['name'],
                $file['path'],
                $file['type'],
                $file['size'],
                $file['mime']
            ]);
        }
        
        // Update submission timestamp
        $sql = "UPDATE submissions 
                SET updated_at = NOW()
                WHERE id = ? AND user_id = ? AND status = 'draft'";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$submission_id, $user_id]);
        
        // Log activity
        log_activity('SUBMISSION_UPDATED', "User updated draft submission ID: $submission_id", $user_id);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating submission draft: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate uploaded file for submission (CUSTOM VERSION)
 * Different from config.php version
 */
function validate_submission_file($file, $allowed_types, $max_size) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['error'])) {
        $errors[] = 'File tidak valid';
        return $errors;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = get_submission_upload_error($file['error']);
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'Ukuran file terlalu besar. Maksimal: ' . format_submission_bytes($max_size);
    }
    
    // Check file type by extension
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension'] ?? '');
    
    if (!in_array($extension, $allowed_types)) {
        $errors[] = 'Tipe file tidak diizinkan. Hanya: ' . strtoupper(implode(', ', $allowed_types));
    }
    
    // Check MIME type for security
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (isset($allowed_mimes[$extension])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($mime_type !== $allowed_mimes[$extension]) {
            $errors[] = 'Tipe MIME file tidak valid';
        }
    }
    
    return $errors;
}

/**
 * Save uploaded file securely for submission
 */
function save_submission_file($file, $document_code, $submission_id) {
    // Validasi file type (hanya PDF, JPG, PNG, JPEG)
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if (!in_array($file_ext, $allowed_extensions)) {
        error_log("ERROR: Invalid file extension: $file_ext");
        return false;
    }
    
    // Validasi file size (maks 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("ERROR: File too large: {$file['size']} bytes");
        return false;
    }
    
    // Define upload directory
    $upload_dir = dirname(__DIR__, 2) . '/storage/uploads/submissions/' . $submission_id;
    
    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("ERROR: Failed to create directory: $upload_dir");
            return false;
        }
        error_log("DEBUG: Created directory: $upload_dir");
    }
    
    // Generate unique filename seperti di verifikasi_functions.php
    $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $safe_name = preg_replace('/[^a-zA-Z0-9-_]/', '_', $original_name);
    $new_filename = $document_code . '_' . uniqid() . '_' . $safe_name . '.' . $file_ext;
    
    $file_path = $upload_dir . '/' . $new_filename;
    $relative_path = 'storage/uploads/submissions/' . $submission_id . '/' . $new_filename;
    
    error_log("DEBUG: Attempting to move file to: $file_path");
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Set proper permissions
        chmod($file_path, 0644);
        
        // Get mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        error_log("SUCCESS: File saved successfully. Size: " . filesize($file_path));
        
        return [
            'name' => $file['name'],
            'path' => $relative_path,
            'type' => $file_ext,
            'size' => $file['size'],
            'mime' => $mime_type
        ];
    } else {
        $error = error_get_last();
        error_log("ERROR: Failed to move uploaded file. Error: " . ($error['message'] ?? 'Unknown error'));
        return false;
    }
}


/**
 * Get vacancy formations
 */
function get_vacancy_formations($db, $vacancy_id) {
    try {
        $sql = "SELECT * FROM vacancy_formations 
                WHERE vacancy_id = ? 
                ORDER BY formation_type, formation_name";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$vacancy_id]);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting vacancy formations: " . $e->getMessage());
        return [];
    }
}

/**
 * Get vacancy details with all information
 */
function get_vacancy_full_details($db, $vacancy_id) {
    try {
        $sql = "SELECT v.*, vt.type_name, vt.description as type_description,
                       u.full_name as created_by_name,
                       COUNT(DISTINCT vf.id) as formation_count,
                       COUNT(DISTINCT vd.id) as document_count,
                       COUNT(DISTINCT vr.id) as requirement_count
                FROM vacancies v
                LEFT JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
                LEFT JOIN users u ON v.created_by = u.id
                LEFT JOIN vacancy_formations vf ON v.id = vf.vacancy_id
                LEFT JOIN vacancy_documents vd ON v.id = vd.vacancy_id
                LEFT JOIN vacancy_requirements vr ON v.id = vr.vacancy_id
                WHERE v.id = ?
                GROUP BY v.id, vt.id, u.id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$vacancy_id]);
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting vacancy full details: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all vacancy types
 */
function get_all_vacancy_types($db) {
    try {
        $sql = "SELECT * FROM vacancy_types WHERE is_active = true ORDER BY type_name";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting vacancy types: " . $e->getMessage());
        return [];
    }
}

/**
 * Submit application with formation selection
 */
function submit_application_with_formation($db, $submission_id, $user_id, $formation_id, $files_data) {
    $db->beginTransaction();
    
    try {
        // Update submission status and formation (only if formation_id > 0)
        if ($formation_id > 0) {
            $sql = "UPDATE submissions 
                    SET status = 'submitted', 
                        formation_id = ?,
                        submission_date = NOW(),
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ? AND status = 'draft'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$formation_id, $submission_id, $user_id]);
        } else {
            // No formation selected
            $sql = "UPDATE submissions 
                    SET status = 'submitted', 
                        submission_date = NOW(),
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ? AND status = 'draft'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$submission_id, $user_id]);
        }
        
        if ($stmt->rowCount() === 0) {
            error_log("ERROR: No rows updated. Submission ID: $submission_id, User ID: $user_id, Status: draft");
            throw new Exception("Gagal mengupdate status pendaftaran - tidak ada baris yang diupdate");
        }
        
        // Insert submission files (if any)
        foreach ($files_data as $file) {
            $sql = "INSERT INTO submission_files 
                    (submission_id, file_name, file_path, file_type, 
                     file_size, mime_type, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $submission_id,
                $file['name'],
                $file['path'],
                $file['type'],
                $file['size'],
                $file['mime']
            ]);
        }
        
        // Update vacancy current_applicants count
        $sql = "UPDATE vacancies v 
                JOIN submissions s ON v.id = s.vacancy_id 
                SET v.current_applicants = v.current_applicants + 1 
                WHERE s.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$submission_id]);
        
        // Log activity
        log_activity('APPLICATION_SUBMITTED', "Submitted application for submission ID: $submission_id with formation: $formation_id", $user_id);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error submitting application: " . $e->getMessage());
        return false;
    }
}

/**
 * Update submission draft with formation
 */
function update_submission_draft_with_formation($db, $submission_id, $user_id, $formation_id, $files_data, $delete_files = []) {
    $db->beginTransaction();
    
    try {
        // Update formation and timestamp (only if formation_id > 0)
        if ($formation_id > 0) {
            $sql = "UPDATE submissions 
                    SET formation_id = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ? AND status = 'draft'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$formation_id, $submission_id, $user_id]);
        } else {
            $sql = "UPDATE submissions 
                    SET updated_at = NOW()
                    WHERE id = ? AND user_id = ? AND status = 'draft'";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$submission_id, $user_id]);
        }
        
        if ($stmt->rowCount() === 0) {
            error_log("ERROR: Cannot update draft. Submission ID: $submission_id, User ID: $user_id");
            throw new Exception("Gagal mengupdate draft - tidak ada baris yang diupdate");
        }
        
        // Delete selected files
        if (!empty($delete_files)) {
            $placeholders = implode(',', array_fill(0, count($delete_files), '?'));
            $sql = "DELETE FROM submission_files 
                    WHERE id IN ($placeholders) AND submission_id = ?";
            
            $params = array_merge($delete_files, [$submission_id]);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Insert new files
        foreach ($files_data as $file) {
            // Check if file already exists
            $sql = "SELECT id FROM submission_files WHERE submission_id = ? AND file_name = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$submission_id, $file['name']]);
            
            if (!$stmt->fetch()) {
                $sql = "INSERT INTO submission_files 
                        (submission_id, file_name, file_path, file_type, 
                         file_size, mime_type, uploaded_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $submission_id,
                    $file['name'],
                    $file['path'],
                    $file['type'],
                    $file['size'],
                    $file['mime']
                ]);
            }
        }
        
        // Log activity
        log_activity('SUBMISSION_UPDATED', "User updated draft submission ID: $submission_id", $user_id);
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating submission draft: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if file exists for a specific document
 */
function has_file_for_document($existing_files, $document_code) {
    foreach ($existing_files as $file) {
        if (strpos($file['file_name'], $document_code) === 0) {
            return $file;
        }
    }
    return false;
}

/**
 * Get upload error message for submission
 */
function get_submission_upload_error($error_code) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server',
        UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form',
        UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diunggah',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
        UPLOAD_ERR_EXTENSION => 'Ekstensi file tidak diizinkan'
    ];
    
    return $errors[$error_code] ?? 'Terjadi kesalahan saat mengunggah file';
}

/**
 * Format bytes to readable size for submission
 */
function format_submission_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get user submissions history (including drafts)
 */
function get_user_submissions($db, $user_id, $limit = null) {
    try {
        $sql = "SELECT s.*, v.title, v.vacancy_code, vt.type_name,
                       v.open_date, v.close_date, v.is_active as vacancy_active,
                       (SELECT COUNT(*) FROM submission_files sf WHERE sf.submission_id = s.id) as file_count,
                       CASE 
                         WHEN s.status = 'submitted' AND v.close_date < CURRENT_DATE THEN 'closed'
                         ELSE s.status
                       END as display_status
                FROM submissions s
                JOIN vacancies v ON s.vacancy_id = v.id
                LEFT JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
                WHERE s.user_id = ?
                ORDER BY 
                    CASE 
                        WHEN s.status = 'draft' THEN 1
                        WHEN s.status = 'submitted' THEN 2
                        WHEN s.status = 'verified' THEN 3
                        WHEN s.status = 'scored' THEN 4
                        WHEN s.status = 'accepted' THEN 5
                        WHEN s.status = 'rejected' THEN 6
                        ELSE 7
                    END,
                    s.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id, $limit]);
        } else {
            $stmt = $db->prepare($sql);
            $stmt->execute([$user_id]);
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting user submissions: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if submission can be edited
 */
function can_edit_submission($submission) {
    if (!$submission) return false;
    
    // Can edit if status is draft
    if ($submission['status'] === 'draft') {
        return true;
    }
    
    return false;
}

/**
 * Delete submission file
 */
function delete_submission_file($db, $file_id, $user_id) {
    try {
        // First, verify the user owns this file
        $sql = "SELECT sf.id 
                FROM submission_files sf
                JOIN submissions s ON sf.submission_id = s.id
                WHERE sf.id = ? AND s.user_id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$file_id, $user_id]);
        
        if (!$stmt->fetch()) {
            return false; // User doesn't own this file
        }
        
        // Get file path
        $sql = "SELECT file_path FROM submission_files WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();
        
        if ($file) {
            // Delete physical file
            $file_path = dirname(__DIR__, 2) . '/' . $file['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete database record
        $sql = "DELETE FROM submission_files WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$file_id]);
        
    } catch (PDOException $e) {
        error_log("Error deleting submission file: " . $e->getMessage());
        return false;
    }
}
?>