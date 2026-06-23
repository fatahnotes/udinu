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
        
        // Tambahkan persyaratan default
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
 * Tambahkan persyaratan default berdasarkan jenis ujian
 */
function add_default_requirements($db, $vacancy_id, $type_id) {
    $stmt = $db->prepare("SELECT type_code FROM vacancy_types WHERE id = ?");
    $stmt->execute([$type_id]);
    $type_code = $stmt->fetchColumn();
    
    if (!$type_code) return;
    
    // Persyaratan default untuk setiap jenis ujian
    $default_requirements = [
        'UD1' => [
            // Ujian Dinas Tingkat I: II/d → III/a
            ['umum', 'Pegawai Negeri Sipil di lingkungan Kemdiktisaintek', 'radio', true, '{"options": ["Ya", "Tidak"]}', 1],
            ['umum', 'Memiliki pangkat Pengatur Tingkat I / golongan ruang II/d', 'validation', true, null, 2],
            ['umum', 'Telah 1 (satu) tahun dalam pangkat/golongan ruang II/d', 'validation', true, null, 3],
            ['umum', 'Penilaian Prestasi Kerja 2 tahun terakhir minimal "Baik"', 'file', true, null, 4],
            ['umum', 'Surat Keputusan Kenaikan Pangkat II/d terakhir', 'file', true, null, 5],
            ['umum', 'Tidak sedang diberhentikan sementara / menerima uang tunggu / cuti di luar tanggungan negara', 'radio', true, '{"options": ["Ya (Tidak Sedang)", "Tidak"]}', 6],
            ['umum', 'Diusulkan oleh Pejabat Pimpinan Tinggi Pratama unit kerja', 'file', true, null, 7],
            ['khusus', 'Surat Keterangan Sehat Jasmani dan Rohani dari Dokter Pemerintah', 'file', true, null, 8],
            ['khusus', 'Pas foto terbaru latar merah ukuran 4x6', 'file', true, null, 9],
        ],
        'UD2' => [
            // Ujian Dinas Tingkat II: III/d → IV/a
            ['umum', 'Pegawai Negeri Sipil di lingkungan Kemdiktisaintek', 'radio', true, '{"options": ["Ya", "Tidak"]}', 1],
            ['umum', 'Memiliki pangkat Penata Tingkat I / golongan ruang III/d', 'validation', true, null, 2],
            ['umum', 'Menduduki Jabatan Struktural Administrator', 'radio', true, '{"options": ["Ya", "Tidak"]}', 3],
            ['umum', 'Belum mengikuti Pelatihan Kepemimpinan Administrator (PKA)', 'radio', true, '{"options": ["Ya (Belum)", "Sudah"]}', 4],
            ['umum', 'Penilaian Prestasi Kerja 2 tahun terakhir minimal "Baik"', 'file', true, null, 5],
            ['umum', 'Surat Keputusan Kenaikan Pangkat III/d terakhir', 'file', true, null, 6],
            ['umum', 'Tidak sedang diberhentikan sementara / menerima uang tunggu / cuti di luar tanggungan negara', 'radio', true, '{"options": ["Ya (Tidak Sedang)", "Tidak"]}', 7],
            ['umum', 'Diusulkan oleh Pejabat Pimpinan Tinggi Pratama unit kerja', 'file', true, null, 8],
            ['khusus', 'Naskah Makalah karya tulis ilmiah sesuai TUPOKSI unit kerja', 'file', true, null, 9],
            ['khusus', 'Surat Keterangan Sehat Jasmani dan Rohani dari Dokter Pemerintah', 'file', true, null, 10],
            ['khusus', 'Pas foto terbaru latar merah ukuran 4x6', 'file', true, null, 11],
        ],
        'UPKP' => [
            // UPKP: Penyesuaian Kenaikan Pangkat
            ['umum', 'Pegawai Negeri Sipil di lingkungan Kemdiktisaintek', 'radio', true, '{"options": ["Ya", "Tidak"]}', 1],
            ['umum', 'Telah memperoleh ijazah lebih tinggi dari jenjang pangkat dan golongan ruang saat ini', 'validation', true, null, 2],
            ['umum', 'Ijazah dari sekolah/perguruan tinggi negeri atau swasta yang terakreditasi', 'file', true, null, 3],
            ['umum', 'Surat Keterangan Memiliki Pendidikan Lebih Tinggi (SKMPLT) jika ada', 'file', false, null, 4],
            ['umum', 'Surat Keputusan Tugas Belajar (SK Tugas Belajar) jika ada', 'file', false, null, 5],
            ['umum', 'Penilaian Prestasi Kerja 2 tahun terakhir minimal "Baik"', 'file', true, null, 6],
            ['umum', 'Diusulkan oleh Pejabat Pimpinan Tinggi Pratama unit kerja', 'file', true, null, 7],
            ['khusus', 'Transkrip nilai ijazah terakhir', 'file', true, null, 8],
            ['khusus', 'Surat Keterangan Sehat Jasmani dan Rohani dari Dokter Pemerintah', 'file', true, null, 9],
            ['khusus', 'Pas foto terbaru latar merah ukuran 4x6', 'file', true, null, 10],
        ]
    ];
    
    if (isset($default_requirements[$type_code])) {
        $stmt = $db->prepare("
            INSERT INTO vacancy_requirements 
            (vacancy_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($default_requirements[$type_code] as $req) {
            $is_required = $req[3] ? 1 : 0;
            $stmt->execute([
                $vacancy_id,
                $req[0], $req[1], $req[2],
                $is_required,
                $req[4] !== null ? $req[4] : null,
                $req[5]
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
        'UD1' => [
            ['Surat Usulan Pimpinan', 'surat_usulan', true],
            ['SK Pangkat II/d Terakhir', 'sk_pangkat', true],
            ['Penilaian Prestasi Kerja 2 Tahun', 'ppk', true],
            ['KTP Elektronik', 'ktp', true],
            ['Kartu Pegawai / NIP', 'karpeg', true],
            ['Ijazah Terakhir (Legalized)', 'ijazah', true],
            ['Surat Keterangan Sehat', 'surat_sehat', true],
            ['Pas Foto 4x6 Latar Merah', 'pas_foto', true],
            ['Surat Pernyataan Tidak Sedang Diberhentikan', 'surat_pernyataan', true],
        ],
        'UD2' => [
            ['Surat Usulan Pimpinan', 'surat_usulan', true],
            ['SK Pangkat III/d Terakhir', 'sk_pangkat', true],
            ['SK Jabatan Struktural Administrator', 'sk_jabatan', true],
            ['Penilaian Prestasi Kerja 2 Tahun', 'ppk', true],
            ['KTP Elektronik', 'ktp', true],
            ['Kartu Pegawai / NIP', 'karpeg', true],
            ['Ijazah Terakhir (Legalized)', 'ijazah', true],
            ['Naskah Makalah Karya Tulis Ilmiah', 'makalah', true],
            ['Surat Keterangan Sehat', 'surat_sehat', true],
            ['Pas Foto 4x6 Latar Merah', 'pas_foto', true],
            ['Surat Pernyataan Tidak Sedang Diberhentikan', 'surat_pernyataan', true],
        ],
        'UPKP' => [
            ['Surat Usulan Pimpinan', 'surat_usulan', true],
            ['Ijazah Baru yang Lebih Tinggi (Legalized)', 'ijazah_baru', true],
            ['Transkrip Nilai Ijazah Baru', 'transkrip', true],
            ['SK Tugas Belajar (jika ada)', 'sk_tugas_belajar', false],
            ['SKMPLT (jika ada)', 'skmplt', false],
            ['Penilaian Prestasi Kerja 2 Tahun', 'ppk', true],
            ['KTP Elektronik', 'ktp', true],
            ['Kartu Pegawai / NIP', 'karpeg', true],
            ['Surat Keterangan Sehat', 'surat_sehat', true],
            ['Pas Foto 4x6 Latar Merah', 'pas_foto', true],
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
            $is_required = $doc[2] ? 1 : 0;
            $stmt->execute([
                $vacancy_id,
                $doc[0], $doc[1],
                $is_required,
                $order++
            ]);
        }
    }
}

?>