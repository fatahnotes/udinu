-- Seed data for Guru Garuda System

-- Insert roles
INSERT INTO roles (role_code, role_name, description) VALUES
('SUPERADMIN', 'Super Administrator', 'Full system administrator with all privileges'),
('ADMIN_VERIFIKATOR', 'Admin Verifikator', 'Administrator untuk verifikasi berkas pendaftaran'),
('ASSESSOR', 'Asesor', 'Penilai seleksi Guru Garuda'),
('USER', 'PNS/Pendaftar', 'Pegawai Negeri Sipil pendaftar ujian'),

-- Insert superadmin user (password: Cint4$#@566)
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('superadmin@gurugaruda.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'Super Administrator', TRUE, TRUE);

-- Insert admin verifikator user
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('admin@gurugaruda.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'Admin Verifikator', TRUE, TRUE);

-- Insert assessor user
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('assessor@gurugaruda.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'Assessor Penilai', TRUE, TRUE);

-- Insert sample user (PNS)
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('pns.contoh@kemdiktisaintek.go.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'PNS Contoh', TRUE, TRUE);

-- Assign roles to users
INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES
(1, (SELECT id FROM roles WHERE role_code = 'SUPERADMIN'), 1),
(2, (SELECT id FROM roles WHERE role_code = 'ADMIN_VERIFIKATOR'), 1),
(3, (SELECT id FROM roles WHERE role_code = 'ASSESSOR'), 1),
(4, (SELECT id FROM roles WHERE role_code = 'USER'), 1);

-- Insert sample profile for user
INSERT INTO profiles (user_id, gender, phone, address, birth_year, last_education, major, institution, is_profile_complete) VALUES
(4, 'Laki-laki', '081234567890', 'Jl. Pendidikan No. 123, Jakarta', 1985, 'S2', 'Pendidikan Matematika', 'Universitas Pendidikan Indonesia', TRUE);

-- Insert ujian types (vacancy_types)
INSERT INTO vacancy_types (type_code, type_name, description, is_active) VALUES
('UD1', 'Ujian Dinas Tingkat I', 'Ujian bagi PNS pangkat Pengatur Tingkat I (II/d) naik ke Penata Muda (III/a). Materi: TWK, TKP, TSI menggunakan CAT.', TRUE),
('UD2', 'Ujian Dinas Tingkat II', 'Ujian bagi PNS pangkat Penata Tingkat I (III/d) naik ke Pembina (IV/a). Materi: CAT + Makalah.', TRUE),
('UPKP', 'UPKP', 'Ujian Penyesuaian Kenaikan Pangkat bagi PNS dengan ijazah lebih tinggi dari jenjang pangkat saat ini.', TRUE);

-- Insert vacancies (ujian)
INSERT INTO vacancies (vacancy_code, vacancy_type_id, title, description, tahun_angkatan, open_date, close_date, max_applicants, is_active) VALUES
('UD1-2025-4a7b', (SELECT id FROM vacancy_types WHERE type_code = 'UD1'), 'Ujian Dinas Tingkat I TA 2025', 
 'Ujian Dinas Tingkat I bagi PNS Kemdiktisaintek yang memenuhi syarat kenaikan pangkat dari Pengatur Tingkat I (II/d) ke Penata Muda (III/a). Materi: TWK, TKP, TSI (100 soal CAT).',
 2025, '2025-01-01', '2025-12-31', 500, TRUE),

('UD2-2025-8c9d', (SELECT id FROM vacancy_types WHERE type_code = 'UD2'), 'Ujian Dinas Tingkat II TA 2025',
 'Ujian Dinas Tingkat II bagi PNS Kemdiktisaintek dari Penata Tingkat I (III/d) ke Pembina (IV/a). Materi: CAT + Penilaian Makalah.',
 2025, '2025-01-01', '2025-12-31', 300, TRUE),

('UPKP-2025-1e2f', (SELECT id FROM vacancy_types WHERE type_code = 'UPKP'), 'UPKP TA 2025',
 'Ujian Penyesuaian Kenaikan Pangkat bagi PNS Kemdiktisaintek dengan ijazah lebih tinggi.',
 2025, '2025-01-01', '2025-12-31', 200, TRUE);

-- Insert sample submission
INSERT INTO submissions (user_id, vacancy_id, status, submission_date) VALUES
(4, 2, 'submitted', CURRENT_TIMESTAMP);

-- Insert sample submission files
INSERT INTO submission_files (submission_id, file_name, file_path, file_type, file_size, mime_type) VALUES
(1, 'ijazah_s1.pdf', '/storage/uploads/user_4_ijazah_s1.pdf', 'pdf', 1024000, 'application/pdf'),
(1, 'sertifikat_pendidik.pdf', '/storage/uploads/user_4_sertifikat.pdf', 'pdf', 512000, 'application/pdf'),
(1, 'foto_3x4.jpg', '/storage/uploads/user_4_foto.jpg', 'jpg', 204800, 'image/jpeg');

-- Insert general announcements
INSERT INTO announcements (title, content, announcement_type, is_published, published_at, created_by) VALUES
('Pembukaan Pendaftaran Ujian Dinas & UPKP 2025', 
 'Dengan ini diumumkan bahwa pendaftaran Ujian Dinas Tingkat I, Ujian Dinas Tingkat II, dan UPKP tahun 2025 telah dibuka.\n\n'
 . 'Pendaftaran dapat dilakukan melalui sistem ini mulai tanggal 1 Januari 2025 hingga 31 Desember 2025.\n\n'
 . 'Jenis Ujian:\n'
 . '1. Ujian Dinas Tingkat I (II/d ke III/a) - TWK, TKP, TSI (100 soal CAT)\n'
 . '2. Ujian Dinas Tingkat II (III/d ke IV/a) - CAT + Makalah\n'
 . '3. UPKP - Penyesuaian Kenaikan Pangkat (ijazah lebih tinggi)\n\n'
 . 'Informasi lebih lanjut dapat menghubungi helpdesk.',
 'general', TRUE, CURRENT_TIMESTAMP, 1),

('Jadwal Pelaksanaan Ujian 2025', 
 'Berikut jadwal pelaksanaan ujian tahun 2025:\n'
 . '1 Jan - 31 Mar: Pendaftaran\n'
 . '1 Apr - 30 Apr: Verifikasi Berkas\n'
 . '1 Mei - 31 Mei: Pelaksanaan Ujian CAT\n'
 . '1 Jun - 30 Jun: Penilaian Makalah (UD2)\n'
 . '1 Jul - 31 Jul: Pengumuman Hasil\n\n'
 . 'Peserta mohon mempersiapkan diri dengan baik.',
 'selection', TRUE, CURRENT_TIMESTAMP, 1),

('Petunjuk Teknis Upload Berkas', 
 'Perhatian untuk semua peserta:\n\n'
 . '1. File berformat PDF/JPG/PNG\n'
 . '2. Ukuran maksimal 5MB per file\n'
 . '3. Pastikan dokumen terbaca dengan jelas\n'
 . '4. Ijazah wajib dilegalisir\n'
 . '5. Gunakan scanner, bukan foto kamera\n\n'
 . 'Jika mengalami kendala, hubungi tim teknis.',
 'technical', TRUE, CURRENT_TIMESTAMP, 1);

-- Insert sample audit logs
INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES
(1, 'LOGIN', 'users', 1, '127.0.0.1', 'Mozilla/5.0'),
(4, 'REGISTER', 'users', 4, '192.168.1.100', 'Mozilla/5.0'),
(4, 'EXAM_REGISTER', 'submissions', 1, '192.168.1.100', 'Mozilla/5.0');

-- Update current applicants count
UPDATE vacancies 
SET current_applicants = (SELECT COUNT(*) FROM submissions WHERE vacancy_id = vacancies.id);

-- Note: Password hashes need to be generated using password_hash('Cint4$#@566', PASSWORD_DEFAULT)
-- Run this PHP code to generate the hashes and update the seed.sql file:
-- <?php echo password_hash('Cint4$#@566', PASSWORD_DEFAULT); ?>

.sidebar-heading {
    font-size: 0.75rem;
    text-transform: uppercase;
}

.nav-link {
    color: #495057;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    margin: 0.125rem 0.5rem;
    transition: all 0.2s;
}

.nav-link:hover {
    background-color: #e9ecef;
    color: #0d6efd;
}

.nav-link.active {
    background-color: #0d6efd;
    color: white;
    font-weight: 600;
}

.nav-link i {
    width: 20px;
    text-align: center;
}

.badge {
    font-size: 0.7em;
    padding: 0.25em 0.5em;
}