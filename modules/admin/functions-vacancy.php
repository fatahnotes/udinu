<?php
// modules/admin/functions-vacancy.php

/**
 * Fungsi untuk membuat kode lowongan unik
 */
function generate_vacancy_code($type_code, $tahun_angkatan) {
    return $type_code . '-' . $tahun_angkatan . '-' . substr(md5(uniqid()), 0, 4);
}

/**
 * Ambil semua jenis lowongan
 */
function get_vacancy_types($db) {
    $stmt = $db->prepare("SELECT * FROM vacancy_types WHERE is_active = TRUE ORDER BY type_name");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Ambil jenis lowongan berdasarkan ID
 */
function get_vacancy_type_by_id($db, $type_id) {
    $stmt = $db->prepare("SELECT * FROM vacancy_types WHERE id = ?");
    $stmt->execute([$type_id]);
    return $stmt->fetch();
}

/**
 * Ambil semua lowongan
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
 * Ambil detail lowongan
 */
function get_vacancy_details($db, $vacancy_id) {
    // Ambil data lowongan
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
    
    // Ambil persyaratan
    $stmt = $db->prepare("
        SELECT * FROM vacancy_requirements 
        WHERE vacancy_id = ? 
        ORDER BY requirement_type, display_order
    ");
    $stmt->execute([$vacancy_id]);
    $vacancy['requirements'] = $stmt->fetchAll();
    
    // Ambil dokumen yang diperlukan
    $stmt = $db->prepare("
        SELECT * FROM vacancy_documents 
        WHERE vacancy_id = ? 
        ORDER BY display_order
    ");
    $stmt->execute([$vacancy_id]);
    $vacancy['documents'] = $stmt->fetchAll();
    
    // Ambil formasi
    $stmt = $db->prepare("
        SELECT * FROM vacancy_formations 
        WHERE vacancy_id = ? 
        ORDER BY id
    ");
    $stmt->execute([$vacancy_id]);
    $vacancy['formations'] = $stmt->fetchAll();
    
    return $vacancy;
}

/**
 * Tambah lowongan baru
 */
function create_vacancy($db, $data, $user_id, $formations = []) {
    try {
        $db->beginTransaction();
        
        // Generate kode lowongan
        $type_stmt = $db->prepare("SELECT type_code FROM vacancy_types WHERE id = ?");
        $type_stmt->execute([$data['vacancy_type_id']]);
        $type = $type_stmt->fetch();
        
        if (!$type) {
            throw new Exception("Jenis lowongan tidak ditemukan di database. ID: " . $data['vacancy_type_id']);
        }
        
        $type_code = $type['type_code'];
        
        $vacancy_code = generate_vacancy_code($type_code, $data['tahun_angkatan']);
        
        // Insert lowongan
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
            throw new Exception("Gagal mendapatkan ID lowongan baru");
        }
        
        // Tambahkan persyaratan default berdasarkan jenis lowongan
        try {
            add_default_requirements($db, $vacancy_id, $data['vacancy_type_id']);
        } catch (Exception $e) {
            throw new Exception("Gagal menambahkan persyaratan default: " . $e->getMessage());
        }
        
        // Tambahkan dokumen default
        try {
            add_default_documents($db, $vacancy_id, $data['vacancy_type_id']);
        } catch (Exception $e) {
            throw new Exception("Gagal menambahkan dokumen default: " . $e->getMessage());
        }
        
        // Tambahkan formasi jika ada
        if (!empty($formations)) {
            foreach ($formations as $formation) {
                $stmt = $db->prepare("
                    INSERT INTO vacancy_formations 
                    (vacancy_id, formation_type, formation_name, jumlah, created_at)
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([
                    $vacancy_id,
                    $formation['type'],
                    $formation['name'],
                    $formation['jumlah']
                ]);
            }
        }
        
        $db->commit();
        
        // Log aktivitas
        if (function_exists('log_activity')) {
            log_activity('VACANCY_CREATE', "Created vacancy: {$vacancy_code}", $user_id);
        }
        
        return $vacancy_id;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Create vacancy error: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        return false;
    }
}

/**
 * Update lowongan
 */
function update_vacancy($db, $vacancy_id, $data, $user_id, $formations = []) {
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
        
        // Hapus formasi lama dan tambahkan yang baru
        $stmt = $db->prepare("DELETE FROM vacancy_formations WHERE vacancy_id = ?");
        $stmt->execute([$vacancy_id]);
        
        // Tambahkan formasi baru
        if (!empty($formations)) {
            foreach ($formations as $formation) {
                $stmt = $db->prepare("
                    INSERT INTO vacancy_formations 
                    (vacancy_id, formation_type, formation_name, jumlah, created_at)
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([
                    $vacancy_id,
                    $formation['type'],
                    $formation['name'],
                    $formation['jumlah']
                ]);
            }
        }
        
        $db->commit();
        
        if (function_exists('log_activity')) {
            log_activity('VACANCY_UPDATE', "Updated vacancy ID: {$vacancy_id}", $user_id);
        }
        
        return true;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Update vacancy error: " . $e->getMessage());
        return false;
    }
}

/**
 * Hapus lowongan (soft delete dengan mengubah is_active)
 */
function delete_vacancy($db, $vacancy_id, $user_id) {
    // Cek apakah ada pendaftar
    $stmt = $db->prepare("SELECT COUNT(*) FROM submissions WHERE vacancy_id = ?");
    $stmt->execute([$vacancy_id]);
    $applicant_count = $stmt->fetchColumn();
    
    if ($applicant_count > 0) {
        // Jika ada pendaftar, ubah status menjadi tidak aktif
        $stmt = $db->prepare("UPDATE vacancies SET is_active = FALSE WHERE id = ?");
        $result = $stmt->execute([$vacancy_id]);
        $action = 'DEACTIVATED';
    } else {
        // Jika tidak ada pendaftar, hapus permanen
        // Hapus formasi terlebih dahulu
        $stmt = $db->prepare("DELETE FROM vacancy_formations WHERE vacancy_id = ?");
        $stmt->execute([$vacancy_id]);
        
        // Hapus lowongan
        $stmt = $db->prepare("DELETE FROM vacancies WHERE id = ?");
        $result = $stmt->execute([$vacancy_id]);
        $action = 'DELETED';
    }
    
    if ($result && function_exists('log_activity')) {
        log_activity('VACANCY_' . $action, "{$action} vacancy ID: {$vacancy_id}", $user_id);
    }
    
    return $result;
}

/**
 * Tambahkan persyaratan default berdasarkan jenis lowongan
 */
function add_default_requirements($db, $vacancy_id, $type_id) {
    // Ambil template persyaratan berdasarkan jenis lowongan
    $stmt = $db->prepare("SELECT type_code FROM vacancy_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $type_code = $stmt->fetchColumn();
    
    if (!$type_code) return;
    
    // Definisikan persyaratan default untuk setiap jenis
    $default_requirements = [
        'KPS' => [
            // Persyaratan Umum
            ['umum', 'Guru Pegawai Negeri Sipil', 'radio', true, '{"options": ["PNS", "Bukan PNS"]}', 1],
            ['umum', 'Berusia paling tinggi 50 (lima puluh) tahun', 'validation', true, null, 2],
            ['umum', 'Memiliki sertifikat pendidik', 'file', true, null, 3],
            ['umum', 'Diutamakan memiliki kualifikasi pendidikan magister atau magister terapan', 'text', false, null, 4],
            ['umum', 'Diutamakan memiliki sertifikat kompetensi tambahan dalam bidang manajemen dan kepemimpinan sekolah', 'file', false, null, 5],
            ['umum', 'Memiliki pengalaman memimpin satuan pendidikan paling singkat 3 (tiga) tahun', 'text', true, null, 6],
            ['umum', 'Menguasai manajemen sekolah, pengembangan kurikulum, dan kepemimpinan pendidikan', 'text', true, null, 7],
            ['umum', 'Mampu merancang strategi peningkatan mutu sekolah dan membina guru serta tenaga kependidikan', 'text', true, null, 8],
            ['umum', 'Sehat jasmani dan rohani, dibuktikan dengan surat keterangan resmi dari rumah sakit pemerintah', 'file', true, null, 9],
            ['umum', 'Tidak pernah diberhentikan dengan hormat tidak atas permintaan sendiri atau tidak dengan hormat sebagai ASN/TNI/POLRI', 'radio', true, '{"options": ["Ya", "Tidak"]}', 10],
            ['umum', 'Tidak menjadi anggota atau pengurus partai politik serta tidak terlibat dalam aktivitas politik praktis', 'radio', true, '{"options": ["Ya", "Tidak"]}', 11],
            ['umum', 'Bersedia mengikuti seluruh proses seleksi dan penempatan di seluruh wilayah Indonesia', 'radio', true, '{"options": ["Ya", "Tidak"]}', 12],
            ['umum', 'Bersih dari narkotika, psikotropika, dan zat adiktif lainnya (NAPZA)', 'file', true, null, 13],
            // Persyaratan Khusus
            ['khusus', 'Memiliki kemampuan Bahasa Inggris aktif (lisan dan tulisan)', 'file', true, null, 14],
            ['khusus', 'Bersedia tinggal di lingkungan sekolah berasrama', 'radio', true, '{"options": ["Ya", "Tidak"]}', 15]
        ],
        'GP' => [
            // Persyaratan untuk Guru
            ['umum', 'Warga Negara Indonesia (WNI)', 'validation', true, null, 1],
            ['umum', 'Memiliki Sertifikat Pendidik melalui PPG', 'file', true, null, 2],
            ['umum', 'Pendidikan minimal S1 yang linier dengan mata pelajaran', 'validation', true, null, 3],
            ['umum', 'Usia maksimal 35 tahun (reguler) atau 45 tahun (mutasi)', 'validation', true, null, 4],
            ['umum', 'Sehat jasmani dan rohani', 'file', true, null, 5],
            ['umum', 'Tidak pernah dijatuhi hukuman pidana penjara', 'radio', true, '{"options": ["Ya", "Tidak"]}', 6],
            ['umum', 'Tidak pernah diberhentikan dengan hormat tidak atas permintaan sendiri', 'radio', true, '{"options": ["Ya", "Tidak"]}', 7],
            ['umum', 'Tidak sedang terikat kontrak kerja tetap dengan instansi lain', 'radio', true, '{"options": ["Ya", "Tidak"]}', 8],
            ['umum', 'Tidak menjadi anggota atau pengurus partai politik', 'radio', true, '{"options": ["Ya", "Tidak"]}', 9],
            ['umum', 'Bersedia mengikuti seluruh proses seleksi dan penempatan', 'radio', true, '{"options": ["Ya", "Tidak"]}', 10],
            ['umum', 'Bersih dari NAPZA', 'file', true, null, 11],
            // Persyaratan Khusus
            ['khusus', 'Memiliki IPK minimal 3.25', 'validation', true, null, 12],
            ['khusus', 'Bersedia tinggal di lingkungan sekolah berasrama', 'radio', true, '{"options": ["Ya", "Tidak"]}', 13],
            ['khusus', 'Memiliki kemampuan merancang pembelajaran berbasis teknologi', 'text', true, null, 14],
            ['khusus', 'Memiliki sertifikat kompetensi tambahan nasional/internasional', 'file', false, null, 15],
            ['khusus', 'Memiliki kemampuan implementasi STEAM', 'text', true, null, 16],
            ['khusus', 'Memiliki sertifikat IELTS minimal 6.5', 'file', true, null, 17]
        ],
        'TKD' => [
            // Persyaratan untuk Tendik
            ['umum', 'Warga Negara Indonesia (WNI)', 'validation', true, null, 1],
            ['umum', 'Sehat jasmani dan rohani', 'file', true, null, 2],
            ['umum', 'Tidak pernah dijatuhi hukuman pidana penjara', 'radio', true, '{"options": ["Ya", "Tidak"]}', 3],
            ['umum', 'Tidak pernah diberhentikan dengan hormat tidak atas permintaan sendiri', 'radio', true, '{"options": ["Ya", "Tidak"]}', 4],
            ['umum', 'Tidak sedang terikat kontrak kerja tetap dengan instansi lain', 'radio', true, '{"options": ["Ya", "Tidak"]}', 5],
            ['umum', 'Tidak menjadi anggota atau pengurus partai politik', 'radio', true, '{"options": ["Ya", "Tidak"]}', 6],
            ['umum', 'Bersedia mengikuti seluruh proses seleksi dan penempatan', 'radio', true, '{"options": ["Ya", "Tidak"]}', 7],
            ['umum', 'Bersih dari NAPZA', 'file', true, null, 8]
        ]
    ];
    
    if (isset($default_requirements[$type_code])) {
        $stmt = $db->prepare("
            INSERT INTO vacancy_requirements 
            (vacancy_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($default_requirements[$type_code] as $req) {
            // Konversi boolean ke integer untuk PostgreSQL
            $is_required = $req[3] ? 1 : 0;
            
            $stmt->execute([
                $vacancy_id,
                $req[0], // requirement_type
                $req[1], // requirement_text
                $req[2], // input_type
                $is_required, // is_required sebagai integer (1/0)
                $req[4] !== null ? $req[4] : null, // options, pastikan null jika kosong
                $req[5]  // display_order
            ]);
        }
    }
}

/**
 * Tambahkan dokumen default
 */
function add_default_documents($db, $vacancy_id, $type_id) {
    $stmt = $db->prepare("SELECT type_code FROM vacancy_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $type_code = $stmt->fetchColumn();
    
    if (!$type_code) return;
    
    $default_documents = [
        'KPS' => [
            ['Surat Lamaran', 'surat_lamaran', true],
            ['Curriculum Vitae', 'cv', true],
            ['KTP', 'ktp', true],
            ['Ijazah S1', 'ijazah_s1', true],
            ['Ijazah S2', 'ijazah_s2', false],
            ['Sertifikat Pendidik', 'sertifikat_pendidik', true],
            ['Transkrip Nilai S1', 'transkrip_s1', true],
            ['Transkrip Nilai S2', 'transkrip_s2', false],
            ['Sertifikat Bahasa Inggris', 'sertifikat_bahasa', true],
            ['Surat Bebas Narkoba', 'bebas_narkoba', true],
            ['Sertifikat Keahlian', 'sertifikat_keahlian', false],
            ['Sertifikat Internasional', 'sertifikat_internasional', false],
            ['Surat Pernyataan Netralitas', 'pernyataan_netralitas', true],
            ['Surat Pernyataan Penempatan', 'pernyataan_penempatan', true]
        ],
        'GP' => [
            ['Surat Lamaran', 'surat_lamaran', true],
            ['Curriculum Vitae', 'cv', true],
            ['KTP', 'ktp', true],
            ['Ijazah S1', 'ijazah_s1', true],
            ['Sertifikat Pendidik', 'sertifikat_pendidik', true],
            ['Transkrip Nilai', 'transkrip', true],
            ['Surat Sehat', 'surat_sehat', true],
            ['Surat Bebas Narkoba', 'bebas_narkoba', true],
            ['Sertifikat IELTS', 'ielts', true],
            ['Sertifikat Kompetensi', 'sertifikat_kompetensi', false],
            ['Surat Pernyataan', 'pernyataan', true]
        ],
        'TKD' => [
            ['Surat Lamaran', 'surat_lamaran', true],
            ['Curriculum Vitae', 'cv', true],
            ['KTP', 'ktp', true],
            ['Ijazah', 'ijazah', true],
            ['Surat Sehat', 'surat_sehat', true],
            ['Surat Bebas Narkoba', 'bebas_narkoba', true],
            ['Surat Pernyataan', 'pernyataan', true]
        ]
    ];
    
    if (isset($default_documents[$type_code])) {
        $stmt = $db->prepare("
            INSERT INTO vacancy_documents 
            (vacancy_id, document_name, document_code, is_required, display_order)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $order = 1;
        foreach ($default_documents[$type_code] as $doc) {
            // Konversi boolean ke integer untuk PostgreSQL
            $is_required = $doc[2] ? 1 : 0;
            
            $stmt->execute([
                $vacancy_id,
                $doc[0],
                $doc[1],
                $is_required, // Konversi true/false ke 1/0
                $order++
            ]);
        }
    }
}

?>