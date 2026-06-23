<?php
// modules/admin/functions-vacancy.php
// Fungsi-fungsi untuk Manajemen Ujian (Ujian Dinas & UPKP)

/**
 * Fungsi untuk membuat kode ujian unik
 */
function generate_vacancy_code($type_code, $tahun_angkatan) {
    return $type_code . '-' . $tahun_angkatan . '-' . substr(md5(uniqid()), 0, 4);
}

/**
 * Ambil semua jenis ujian
 */
function get_vacancy_types($db) {
    $stmt = $db->prepare("SELECT * FROM vacancy_types WHERE is_active = TRUE ORDER BY type_name");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Ambil jenis ujian berdasarkan ID
 */
function get_vacancy_type_by_id($db, $type_id) {
    $stmt = $db->prepare("SELECT * FROM vacancy_types WHERE id = ?");
    $stmt->execute([$type_id]);
    return $stmt->fetch();
}

/**
 * Ambil semua ujian
 */
function get_all_vacancies($db, $filters = []) {
    $where = [];
    $params = [];
    
    if (!empty($filters['type_id'])) {
        $where[] = "v.vacancy_type_id = ?";
        $params[] = $filters['type_id'];
    }
    
    if (!empty($filters['tahun_angkatan'])) {
        $where[] = "v.tahun_angkatan = ?";
        $params[] = $filters['tahun_angkatan'];
    }
    
    if (isset($filters['is_active'])) {
        $where[] = "v.is_active = ?";
        $params[] = $filters['is_active'];
    }
    
    $where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "
        SELECT 
            v.*,
            vt.type_name,
            vt.type_code,
            u.full_name as created_by_name,
            COUNT(s.id) as total_applicants
        FROM vacancies v
        LEFT JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
        LEFT JOIN users u ON v.created_by = u.id
        LEFT JOIN submissions s ON v.id = s.vacancy_id
        $where_clause
        GROUP BY v.id, vt.id, u.id
        ORDER BY v.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ambil detail ujian
 */
function get_vacancy_details($db, $vacancy_id) {
    // Ambil data ujian
    $stmt = $db->prepare("
        SELECT 
            v.*,
            vt.type_name,
            vt.type_code,
            u.full_name as created_by_name
        FROM vacancies v
        LEFT JOIN vacancy_types vt ON v.vacancy_type_id = vt.id
        LEFT JOIN users u ON v.created_by = u.id
        WHERE v.id = ?
    ");
    $stmt->execute([$vacancy_id]);
    $vacancy = $stmt->fetch();
    
    if (!$vacancy) return null;
    
    // Ambil persyaratan (hanya yang per-vacancy, bukan template)
    $stmt = $db->prepare("
        SELECT * FROM vacancy_requirements 
        WHERE vacancy_id = ? 
        ORDER BY requirement_type, display_order
    ");
    $stmt->execute([$vacancy_id]);
    $vacancy['requirements'] = $stmt->fetchAll();
    
    // Ambil dokumen yang diperlukan (hanya yang per-vacancy)
    $stmt = $db->prepare("
        SELECT * FROM vacancy_documents 
        WHERE vacancy_id = ? 
        ORDER BY display_order
    ");
    $stmt->execute([$vacancy_id]);
    $vacancy['documents'] = $stmt->fetchAll();
    
    return $vacancy;
}

/**
 * Tambah ujian baru
 */
function create_vacancy($db, $data, $user_id) {
    try {
        $db->beginTransaction();
        
        // Generate kode ujian
        $type_stmt = $db->prepare("SELECT type_code FROM vacancy_types WHERE id = ?");
        $type_stmt->execute([$data['vacancy_type_id']]);
        $type = $type_stmt->fetch();
        
        if (!$type) {
            throw new Exception("Jenis ujian tidak ditemukan di database. ID: " . $data['vacancy_type_id']);
        }
        
        $type_code = $type['type_code'];
        $vacancy_code = generate_vacancy_code($type_code, $data['tahun_angkatan']);
        
        // Insert ujian
        $stmt = $db->prepare("
            INSERT INTO vacancies 
            (vacancy_code, vacancy_type_id, title, description, tahun_angkatan, 
             open_date, close_date, max_applicants, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING id
        ");
        
        $stmt->execute([
            $vacancy_code,
            $data['vacancy_type_id'],
            $data['title'],
            $data['description'],
            $data['tahun_angkatan'],
            $data['open_date'],
            $data['close_date'],
            $data['max_applicants'] ?: null,
            $data['is_active'] ? 1 : 0,
            $user_id
        ]);
        
        $vacancy_id = $stmt->fetchColumn();
        
        if (!$vacancy_id) {
            throw new Exception("Gagal mendapatkan ID ujian baru");
        }
        
        // Copy persyaratan & dokumen dari template jenis ujian
        try {
            copy_type_templates($db, $vacancy_id, $data['vacancy_type_id']);
        } catch (Exception $e) {
            throw new Exception("Gagal menyalin template: " . $e->getMessage());
        }
        
        $db->commit();
        
        if (function_exists('log_activity')) {
            log_activity('EXAM_CREATE', "Membuat ujian: {$vacancy_code}", $user_id);
        }
        
        return $vacancy_id;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Create exam error: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        return false;
    }
}

/**
 * Update ujian
 */
function update_vacancy($db, $vacancy_id, $data, $user_id) {
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            UPDATE vacancies 
            SET title = ?, description = ?, tahun_angkatan = ?,
                open_date = ?, close_date = ?, max_applicants = ?,
                is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['tahun_angkatan'],
            $data['open_date'],
            $data['close_date'],
            $data['max_applicants'] ?: null,
            $data['is_active'] ? 1 : 0,
            $vacancy_id
        ]);
        
        $db->commit();
        
        if (function_exists('log_activity')) {
            log_activity('EXAM_UPDATE', "Memperbarui ujian ID: {$vacancy_id}", $user_id);
        }
        
        return true;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Update exam error: " . $e->getMessage());
        return false;
    }
}

/**
 * Hapus ujian (soft delete)
 */
function delete_vacancy($db, $vacancy_id, $user_id) {
    // Cek apakah ada peserta
    $stmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE vacancy_id = ?");
    $stmt->execute([$vacancy_id]);
    $applicant_count = $stmt->fetchColumn();
    
    if ($applicant_count > 0) {
        // Jika ada peserta, nonaktifkan saja
        $stmt = $db->prepare("UPDATE vacancies SET is_active = FALSE WHERE id = ?");
        $result = $stmt->execute([$vacancy_id]);
        $action = 'DEACTIVATED';
    } else {
        // Jika tidak ada peserta, hapus permanen
        $stmt = $db->prepare("DELETE FROM vacancies WHERE id = ?");
        $result = $stmt->execute([$vacancy_id]);
        $action = 'DELETED';
    }
    
    if ($result && function_exists('log_activity')) {
        log_activity('EXAM_' . $action, "{$action} exam ID: {$vacancy_id}", $user_id);
    }
    
    return $result;
}

/**
 * Copy template documents & requirements dari jenis ujian ke vacancy baru
 */
function copy_type_templates($db, $vacancy_id, $type_id) {
    // Copy dokumen dari template
    $stmt = $db->prepare("
        INSERT INTO vacancy_documents (vacancy_id, vacancy_type_id, document_name, document_code, is_required, display_order)
        SELECT ?, vacancy_type_id, document_name, document_code, is_required, display_order
        FROM vacancy_documents
        WHERE vacancy_type_id = ? AND vacancy_id IS NULL
    ");
    $stmt->execute([$vacancy_id, $type_id]);
    
    // Copy persyaratan dari template
    $stmt = $db->prepare("
        INSERT INTO vacancy_requirements (vacancy_id, vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
        SELECT ?, vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order
        FROM vacancy_requirements
        WHERE vacancy_type_id = ? AND vacancy_id IS NULL
    ");
    $stmt->execute([$vacancy_id, $type_id]);
}

/**
 * [DEPRECATED] — kept for backward compatibility during migration
 */
function add_default_requirements($db, $vacancy_id, $type_id) {
    copy_type_templates($db, $vacancy_id, $type_id);
}

/**
 * [DEPRECATED] — kept for backward compatibility during migration
 */
function add_default_documents($db, $vacancy_id, $type_id) {
    // Already handled by copy_type_templates above
}
?>