-- Seed data for Guru Garuda System

-- Insert roles
INSERT INTO roles (role_code, role_name, description) VALUES
('SUPERADMIN', 'Super Administrator', 'Full system administrator with all privileges'),
('ADMIN_VERIFIKATOR', 'Admin Verifikator', 'Administrator untuk verifikasi berkas pendaftaran'),
('ASSESSOR', 'Asesor', 'Penilai seleksi Guru Garuda'),
('USER', 'Guru/Pendaftar', 'Pengguna guru yang mendaftar seleksi');

-- Insert superadmin user (password: Cint4$#@566)
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('superadmin@gurugaruda.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'Super Administrator', TRUE, TRUE);

-- Insert admin verifikator user
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('admin@gurugaruda.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'Admin Verifikator', TRUE, TRUE);

-- Insert assessor user
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('assessor@gurugaruda.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'Assessor Penilai', TRUE, TRUE);

-- Insert sample user
INSERT INTO users (email, password_hash, full_name, is_email_verified, is_active) VALUES
('guru.contoh@sekolah.id', '$2y$10$6XWwQxhrHvYBmF.y2LWuce3e/vbU8gSJ2rwbM1GA2Tzjog2CIr21S', 'Guru Contoh', TRUE, TRUE);

-- Assign roles to users
INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES
(1, (SELECT id FROM roles WHERE role_code = 'SUPERADMIN'), 1),
(2, (SELECT id FROM roles WHERE role_code = 'ADMIN_VERIFIKATOR'), 1),
(3, (SELECT id FROM roles WHERE role_code = 'ASSESSOR'), 1),
(4, (SELECT id FROM roles WHERE role_code = 'USER'), 1);

-- Insert sample profile for user
INSERT INTO profiles (user_id, gender, phone, address, birth_year, last_education, major, institution, is_profile_complete) VALUES
(4, 'Laki-laki', '081234567890', 'Jl. Pendidikan No. 123, Jakarta', 1985, 'S2', 'Pendidikan Matematika', 'Universitas Pendidikan Indonesia', TRUE);

-- Insert vacancies
INSERT INTO vacancies (vacancy_code, title, description, requirements, open_date, close_date, max_applicants, is_active) VALUES
('GG-KS-2024', 'Seleksi Kepala Sekolah Garuda', 'Program seleksi kepala sekolah berprestasi untuk sekolah-sekolah unggulan nasional.', 
 '1. Minimal S2 Pendidikan
2. Pengalaman minimal 5 tahun sebagai guru
3. Memiliki sertifikat kepala sekolah
4. Usia maksimal 55 tahun',
 '2024-01-01', '2024-12-31', 100, TRUE),

('GG-GS-2024', 'Seleksi Guru Sekolah Garuda', 'Program seleksi guru berprestasi untuk mengajar di sekolah-sekolah Garuda.',
 '1. Minimal S1 Pendidikan sesuai bidang
2. Memiliki sertifikat pendidik
3. Pengalaman mengajar minimal 3 tahun
4. Usia maksimal 50 tahun',
 '2024-01-01', '2024-12-31', 200, TRUE),

('GG-TK-2024', 'Seleksi Tenaga Kependidikan Garuda', 'Program seleksi tenaga kependidikan untuk mendukung administrasi sekolah Garuda.',
 '1. Minimal D3 semua jurusan
2. Menguasai komputer dan administrasi
3. Pengalaman kerja minimal 2 tahun
4. Usia maksimal 45 tahun',
 '2024-01-01', '2024-12-31', 50, TRUE);

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
('Pembukaan Pendaftaran Guru Garuda 2024', 
 'Dengan ini diumumkan bahwa pendaftaran seleksi Guru Garuda tahun 2024 telah dibuka. 
  Pendaftaran dapat dilakukan melalui sistem ini mulai tanggal 1 Januari 2024 hingga 31 Desember 2024.
  
  Persyaratan:
  1. Memenuhi kualifikasi pendidikan
  2. Memiliki pengalaman mengajar
  3. Bersedia ditempatkan di seluruh Indonesia
  
  Informasi lebih lanjut dapat menghubungi helpdesk kami.',
 'general', TRUE, CURRENT_TIMESTAMP, 1),

('Jadwal Seleksi Tahap 1', 
 'Berikut jadwal seleksi tahap 1:
  - 1-31 Januari: Pendaftaran
  - 1-15 Februari: Verifikasi Berkas
  - 16-29 Februari: Seleksi Administrasi
  - 1-15 Maret: Pengumuman Tahap 1
  
  Peserta yang lolos tahap 1 akan dihubungi untuk tahap selanjutnya.',
 'selection', TRUE, CURRENT_TIMESTAMP, 1),

('Perbaikan Sistem Upload', 
 'Perhatian untuk semua peserta:
  
  Telah dilakukan perbaikan pada sistem upload berkas. Pastikan:
  1. File berformat PDF/JPG/PNG
  2. Ukuran maksimal 5MB per file
  3. Nama file jelas dan sesuai
  
  Jika mengalami kendala, silakan hubungi tim teknis.',
 'technical', TRUE, CURRENT_TIMESTAMP, 1);

-- Insert sample audit logs
INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES
(1, 'LOGIN', 'users', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(4, 'REGISTER', 'users', 4, '192.168.1.100', 'Mozilla/5.0 (Android 10; Mobile)'),
(4, 'SUBMISSION_CREATE', 'submissions', 1, '192.168.1.100', 'Mozilla/5.0 (Android 10; Mobile)');

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