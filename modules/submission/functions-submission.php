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
                AND status NOT IN ('draft', 'rejected_satker', 'rejected_pusat', 'not_passed')";
        
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
                AND s.status NOT IN ('draft', 'rejected_satker', 'rejected_pusat', 'not_passed')
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
            } elseif ($existing_submission['status'] === 'rejected_satker') {
                // Allow re-submission after rejection by satker
                return ['can_apply' => true, 'vacancy' => $vacancy, 'submission_id' => $existing_submission['id']];
            } else {
                return ['can_apply' => false, 'reason' => 'Anda sudah mendaftar lowongan ini'];
            }
        }
        
        // Check if user has other active submissions
        // PERBAIKAN: Hapus alias 's.' karena tabel tidak diberi alias
        $sql = "SELECT COUNT(*) FROM submissions 
                WHERE user_id = ? 
                AND status NOT IN ('draft', 'rejected_satker', 'rejected_pusat', 'not_passed')
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
        // Check if draft or rejected_satker already exists — reuse it
        $sql = "SELECT id, status FROM submissions 
                WHERE user_id = ? AND vacancy_id = ? AND status IN ('draft', 'rejected_satker')";
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $vacancy_id]);
        
        if ($existing = $stmt->fetch()) {
            // If rejected_satker, reset to draft for re-submission
            if ($existing['status'] === 'rejected_satker') {
                $reset = $db->prepare("UPDATE submissions SET status = 'draft', updated_at = NOW() WHERE id = ?");
                $reset->execute([$existing['id']]);
            }
            return $existing['id'];
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
            $docNumber = $file['document_number'] ?? null;
            $docDate   = $file['document_date'] ?? null;
            if ($docNumber === '') $docNumber = null;
            if ($docDate === '') $docDate = null;

            $sql = "INSERT INTO submission_files 
                    (submission_id, file_name, file_path, file_type, 
                     file_size, mime_type, document_id, document_number, document_date, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $submission_id,
                $file['name'],
                $file['path'],
                $file['type'],
                $file['size'],
                $file['mime'],
                ($file['document_id'] ?? null) ?: null,
                $docNumber,
                $docDate,
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
 * Cancel submission draft — hapus total termasuk file fisik
 */
function cancel_submission_draft($db, $submission_id, $user_id) {
    try {
        // Get all file paths BEFORE deleting DB records
        $stmt = $db->prepare("SELECT file_path FROM submission_files WHERE submission_id = ?");
        $stmt->execute([$submission_id]);
        $files_to_delete = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $db->beginTransaction();
        
        // Delete submission files from DB
        $stmt = $db->prepare("DELETE FROM submission_files WHERE submission_id = ?");
        $stmt->execute([$submission_id]);

        // Delete verification results if any
        $stmt = $db->prepare("DELETE FROM verification_results WHERE submission_id = ?");
        $stmt->execute([$submission_id]);

        // Delete the draft submission (also allow deleting rejected_satker)
        $stmt = $db->prepare("DELETE FROM submissions WHERE id = ? AND user_id = ? AND status IN ('draft', 'rejected_satker')");
        $stmt->execute([$submission_id, $user_id]);

        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            error_log("CANCEL_DRAFT: No draft found to delete. ID: $submission_id, User: $user_id");
            return false;
        }

        $db->commit();
        
        // Delete physical files from disk AFTER successful DB commit
        $base_path = dirname(__DIR__, 2);
        foreach ($files_to_delete as $file_path) {
            $full_path = $base_path . '/' . $file_path;
            if (file_exists($full_path)) {
                unlink($full_path);
                error_log("CANCEL_DRAFT: Deleted physical file: $full_path");
            }
        }
        
        // Try to remove the submission directory if empty
        $upload_dir = $base_path . '/storage/uploads/submissions/' . $submission_id;
        if (is_dir($upload_dir)) {
            // Remove any remaining files in directory
            $remaining = glob($upload_dir . '/*');
            foreach ($remaining as $f) {
                if (is_file($f)) unlink($f);
            }
            // Remove directory if empty
            @rmdir($upload_dir);
        }
        
        log_activity('SUBMISSION_CANCELLED', "User cancelled draft submission ID: $submission_id", $user_id);
        return true;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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
            $docNumber = $file['document_number'] ?? null;
            $docDate   = $file['document_date'] ?? null;
            if ($docNumber === '') $docNumber = null;
            if ($docDate === '') $docDate = null;

            $sql = "INSERT INTO submission_files 
                    (submission_id, file_name, file_path, file_type, 
                     file_size, mime_type, document_id, document_number, document_date, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $submission_id,
                $file['name'],
                $file['path'],
                $file['type'],
                $file['size'],
                $file['mime'],
                ($file['document_id'] ?? null) ?: null,
                $docNumber,
                $docDate,
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
 * @param array $file            $_FILES array element
 * @param string $document_code  Document code from vacancy_documents
 * @param int    $submission_id  Submission ID
 * @param string $document_number Optional document number
 * @param string $document_date   Optional document date (Y-m-d)
 * @param int    $document_id     Optional vacancy_documents.id for FK
 * @return array|false
 */
function save_submission_file($file, $document_code, $submission_id, $document_number = '', $document_date = '', $document_id = 0) {
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
            'mime' => $mime_type,
            'document_number' => $document_number,
            'document_date' => $document_date,
            'document_id' => $document_id,
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
 * UPDATED: robust transaction handling + rowCount verification
 */
function submit_application_with_formation($db, $submission_id, $user_id, $formation_id, $files_data) {
    error_log("SUBMIT_FUNC: Called with submission_id=$submission_id, user_id=$user_id, formation_id=$formation_id, files_count=" . count($files_data));
    
    // Safety: clean up any dangling transaction
    if ($db->inTransaction()) {
        error_log("SUBMIT_FUNC WARNING: Rolling back unexpected active transaction");
        $db->rollBack();
    }
    
    $db->beginTransaction();
    
    try {
        // Step 1: Verify submission exists as draft
        $check = $db->prepare("SELECT id, vacancy_id FROM submissions WHERE id = ? AND user_id = ? AND status = 'draft'");
        $check->execute([$submission_id, $user_id]);
        $draft = $check->fetch();
        if (!$draft) {
            $db->rollBack();
            error_log("SUBMIT_FUNC FAIL: Draft not found. Submission: $submission_id, User: $user_id");
            return false;
        }
        error_log("SUBMIT_FUNC: Draft verified. Vacancy ID: " . $draft['vacancy_id']);

        // Step 2: UPDATE status from 'draft' to 'submitted'
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
            $sql = "UPDATE submissions 
                    SET status = 'submitted', 
                        submission_date = NOW(),
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ? AND status = 'draft'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$submission_id, $user_id]);
        }
        
        $rows_updated = $stmt->rowCount();
        error_log("SUBMIT_FUNC: UPDATE affected $rows_updated rows (expected 1)");
        
        if ($rows_updated === 0) {
            throw new Exception("UPDATE affected 0 rows — status may already be changed or draft not found");
        }
        
        // Step 3: Verify status changed (inside transaction)
        $check = $db->prepare("SELECT status FROM submissions WHERE id = ?");
        $check->execute([$submission_id]);
        $current_status = $check->fetchColumn();
        error_log("SUBMIT_FUNC: Status after UPDATE (inside tx) = " . ($current_status ?: 'NULL'));
        
        if ($current_status !== 'submitted') {
            throw new Exception("Status is '$current_status', expected 'submitted'. Trigger may have rolled back the change.");
        }
        
        // Step 4: Insert any new submission files
        foreach ($files_data as $file) {
            $docNumber = ($file['document_number'] ?? null);
            $docDate   = ($file['document_date'] ?? null);
            if ($docNumber === '') $docNumber = null;
            if ($docDate === '') $docDate = null;

            $sql = "INSERT INTO submission_files 
                    (submission_id, file_name, file_path, file_type, file_size, mime_type, document_id, document_number, document_date, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $submission_id, $file['name'], $file['path'], $file['type'],
                $file['size'], $file['mime'],
                ($file['document_id'] ?? null) ?: null,
                $docNumber, $docDate,
            ]);
        }
        
        // Step 5: Update vacancy applicant count
        $sql = "UPDATE vacancies SET current_applicants = current_applicants + 1 WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$draft['vacancy_id']]);
        
        // Step 6: COMMIT
        $db->commit();
        error_log("SUBMIT_FUNC: Transaction COMMITTED successfully");
        
        // Step 7: FINAL VERIFICATION (outside transaction)
        $finalCheck = $db->prepare("SELECT status FROM submissions WHERE id = ?");
        $finalCheck->execute([$submission_id]);
        $finalStatus = $finalCheck->fetchColumn();
        error_log("SUBMIT_FUNC: Final status after commit = " . ($finalStatus ?: 'NULL'));
        
        if ($finalStatus !== 'submitted') {
            error_log("SUBMIT_FUNC CRITICAL: Status is '$finalStatus' after commit! A trigger is likely reverting the change.");
            return false;
        }
        
        log_activity('APPLICATION_SUBMITTED', "Submitted application for submission ID: $submission_id", $user_id);
        error_log("SUBMIT_FUNC SUCCESS: submission_id=$submission_id verified as 'submitted'");
        return true;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("SUBMIT_FUNC ERROR: " . $e->getMessage() . " | submission_id=$submission_id");
        error_log("SUBMIT_FUNC TRACE: " . $e->getTraceAsString());
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
        
        // Delete selected files (DB + physical)
        if (!empty($delete_files)) {
            // Get file paths BEFORE deleting DB records
            $placeholders = implode(',', array_fill(0, count($delete_files), '?'));
            $sql = "SELECT file_path FROM submission_files 
                    WHERE id IN ($placeholders) AND submission_id = ?";
            $params = array_merge($delete_files, [$submission_id]);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $paths_to_delete = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Delete from DB
            $sql = "DELETE FROM submission_files 
                    WHERE id IN ($placeholders) AND submission_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            // Delete physical files from disk
            $base_path = dirname(__DIR__, 2);
            foreach ($paths_to_delete as $fp) {
                $full_path = $base_path . '/' . $fp;
                if (file_exists($full_path)) {
                    unlink($full_path);
                    error_log("DELETE_FILE: Removed physical file: $full_path");
                }
            }
        }
        
        // Insert new files
        foreach ($files_data as $file) {
            // Check if file already exists
            $sql = "SELECT id FROM submission_files WHERE submission_id = ? AND file_name = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$submission_id, $file['name']]);
            
            if (!$stmt->fetch()) {
                $docNumber = $file['document_number'] ?? null;
                $docDate   = $file['document_date'] ?? null;
                if ($docNumber === '') $docNumber = null;
                if ($docDate === '') $docDate = null;

                $sql = "INSERT INTO submission_files 
                        (submission_id, file_name, file_path, file_type, 
                         file_size, mime_type, document_id, document_number, document_date, uploaded_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $submission_id,
                    $file['name'],
                    $file['path'],
                    $file['type'],
                    $file['size'],
                    $file['mime'],
                    ($file['document_id'] ?? null) ?: null,
                    $docNumber,
                    $docDate,
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
                        WHEN s.status = 'verified_satker' THEN 3
                        WHEN s.status = 'verified_pusat' THEN 4
                        WHEN s.status = 'exam_phase' THEN 5
                        WHEN s.status = 'scoring_phase' THEN 6
                        WHEN s.status = 'announced' THEN 7
                        WHEN s.status = 'certified' THEN 8
                        WHEN s.status = 'passed' THEN 9
                        WHEN s.status = 'not_passed' THEN 9
                        WHEN s.status LIKE 'rejected%' THEN 10
                        ELSE 11
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

/**
 * Update a submission that has already been submitted (without changing status).
 * Allows user to edit metadata and upload additional files while keeping 'submitted' status.
 * 
 * @param PDO   $db             Database connection
 * @param int   $submission_id  Submission ID
 * @param int   $user_id        User ID
 * @param int   $formation_id   Formation ID (optional)
 * @param array $files_data     New files to add
 * @return bool
 */
function update_submitted_submission($db, $submission_id, $user_id, $formation_id, $files_data) {
    error_log("UPDATE_SUBMITTED_FUNC: Called with submission_id=$submission_id, user_id=$user_id, formation_id=$formation_id, files_count=" . count($files_data));
    
    try {
        // Verify submission exists and belongs to user
        $check = $db->prepare("SELECT id, status FROM submissions WHERE id = ? AND user_id = ?");
        $check->execute([$submission_id, $user_id]);
        $submission = $check->fetch();
        
        if (!$submission) {
            error_log("UPDATE_SUBMITTED_FUNC FAIL: Submission not found. ID: $submission_id, User: $user_id");
            return false;
        }
        
        if ($submission['status'] === 'draft') {
            error_log("UPDATE_SUBMITTED_FUNC FAIL: Cannot use update_submitted on draft. Use submit instead.");
            return false;
        }
        
        // Update formation if provided
        if ($formation_id > 0) {
            $sql = "UPDATE submissions SET formation_id = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$formation_id, $submission_id, $user_id]);
        } else {
            // Just update timestamp
            $sql = "UPDATE submissions SET updated_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$submission_id, $user_id]);
        }
        
        // Insert any new files
        foreach ($files_data as $file) {
            $docNumber = $file['document_number'] ?? null;
            $docDate   = $file['document_date'] ?? null;
            if ($docNumber === '') $docNumber = null;
            if ($docDate === '') $docDate = null;

            $sql = "INSERT INTO submission_files 
                    (submission_id, file_name, file_path, file_type, 
                     file_size, mime_type, document_id, document_number, document_date, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $submission_id,
                $file['name'],
                $file['path'],
                $file['type'],
                $file['size'],
                $file['mime'],
                ($file['document_id'] ?? null) ?: null,
                $docNumber,
                $docDate,
            ]);
        }
        
        log_activity('SUBMISSION_UPDATED', "User updated submitted application ID: $submission_id", $user_id);
        error_log("UPDATE_SUBMITTED_FUNC SUCCESS: submission_id=$submission_id");
        
        return true;
        
    } catch (Exception $e) {
        error_log("UPDATE_SUBMITTED_FUNC ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Update document_number and document_date for existing submission files
 * based on form POST data. Used when saving draft or submitting.
 */
function update_existing_file_metadata($db, $submission_id, $user_id, $documents, $post_data) {
    try {
        // Verify submission belongs to user
        $sql = "SELECT id FROM submissions WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$submission_id, $user_id]);
        if (!$stmt->fetch()) return false;

        // Get all existing files for this submission
        $sql = "SELECT * FROM submission_files WHERE submission_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$submission_id]);
        $existing_files = $stmt->fetchAll();

        foreach ($documents as $doc) {
            $doc_id = $doc['id'];
            $doc_code = $doc['document_code'];
            $new_number = trim($post_data['doc_number_' . $doc_id] ?? '');
            $new_date   = trim($post_data['doc_date_' . $doc_id] ?? '');

            // Find the matching existing file for this document
            foreach ($existing_files as $ef) {
                if (strpos($ef['file_name'], $doc_code) !== false || strpos($ef['file_path'], $doc_code) !== false) {
                    // Check if values actually changed
                    $cur_number = $ef['document_number'] ?? '';
                    $cur_date   = $ef['document_date'] ?? '';
                    if ($cur_number !== $new_number || $cur_date !== $new_date) {
                        $sql = "UPDATE submission_files 
                                SET document_number = ?, document_date = ? 
                                WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([
                            ($new_number !== '' ? $new_number : null),
                            ($new_date !== '' ? $new_date : null),
                            $ef['id'],
                        ]);
                        error_log("DEBUG: Updated metadata for file ID {$ef['id']}: number=$new_number, date=$new_date");
                    }
                    break;
                }
            }
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error updating file metadata: " . $e->getMessage());
        return false;
    }
}
?>