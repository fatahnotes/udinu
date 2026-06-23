-- ============================================
-- JABATAN TABLE — Master Data Jabatan ASN
-- ============================================
CREATE TABLE IF NOT EXISTS jabatan (
    id SERIAL PRIMARY KEY,
    kode VARCHAR(30) UNIQUE NOT NULL,
    nama_jabatan VARCHAR(255) NOT NULL,
    kategori VARCHAR(50) DEFAULT 'fungsional',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- SEED: Daftar Jabatan Fungsional & Struktural
-- ============================================
INSERT INTO jabatan (kode, nama_jabatan, kategori) VALUES
-- Jabatan Fungsional Guru
('JFU-GURU-PERTAMA', 'Guru Pertama', 'fungsional'),
('JFU-GURU-MUDA', 'Guru Muda', 'fungsional'),
('JFU-GURU-MADYA', 'Guru Madya', 'fungsional'),
('JFU-GURU-UTAMA', 'Guru Utama', 'fungsional'),

-- Jabatan Fungsional Umum
('JFU-PRANATA-KOMPUTER', 'Pranata Komputer', 'fungsional'),
('JFU-PRANATA-HUMAS', 'Pranata Humas', 'fungsional'),
('JFU-ARSIPARIS', 'Arsiparis', 'fungsional'),
('JFU-PUSTAKAWAN', 'Pustakawan', 'fungsional'),
('JFU-ANALIS-KEPEG', 'Analis Kepegawaian', 'fungsional'),
('JFU-PERENCANA', 'Perencana', 'fungsional'),
('JFU-AUDITOR', 'Auditor', 'fungsional'),
('JFU-WIDYAISWARA', 'Widyaiswara', 'fungsional'),
('JFU-DOSEN', 'Dosen', 'fungsional'),
('JFU-PENELITI', 'Peneliti', 'fungsional'),
('JFU-PEREKAYASA', 'Perekayasa', 'fungsional'),
('JFU-PENYULUH', 'Penyuluh', 'fungsional'),
('JFU-PENGAWAS-SEK', 'Pengawas Sekolah', 'fungsional'),
('JFU-PAMONG-BELAJAR', 'Pamong Belajar', 'fungsional'),
('JFU-PENGEMBANG-TEK', 'Pengembang Teknologi Pembelajaran', 'fungsional'),
('JFU-POLISI-PP', 'Polisi Pamong Praja', 'fungsional'),
('JFU-PEMADAM', 'Pemadam Kebakaran', 'fungsional'),
('JFU-PENYULUH-TANI', 'Penyuluh Pertanian', 'fungsional'),
('JFU-PENGAWAS-NAKER', 'Pengawas Ketenagakerjaan', 'fungsional'),
('JFU-PENGAWAS-LH', 'Pengawas Lingkungan Hidup', 'fungsional'),
('JFU-ANALIS-KEBIJAK', 'Analis Kebijakan', 'fungsional'),
('JFU-STATISTISI', 'Statistisi', 'fungsional'),
('JFU-SURVEYOR', 'Surveyor Pemetaan', 'fungsional'),
('JFU-TEKNISI-LIT', 'Teknisi Penelitian', 'fungsional'),
('JFU-PENGELOLA-PBJ', 'Pengelola Pengadaan Barang/Jasa', 'fungsional'),

-- Jabatan Struktural
('JST-KEPALA-BIRO', 'Kepala Biro', 'struktural'),
('JST-KEPALA-BAGIAN', 'Kepala Bagian', 'struktural'),
('JST-KEPALA-SUBBAG', 'Kepala Subbagian', 'struktural'),
('JST-KEPALA-SEKSI', 'Kepala Seksi', 'struktural'),
('JST-KEPALA-BIDANG', 'Kepala Bidang', 'struktural'),
('JST-KEPALA-SUBID', 'Kepala Subbidang', 'struktural'),
('JST-KEPALA-UPT', 'Kepala UPT', 'struktural'),
('JST-KEPALA-TU', 'Kepala Tata Usaha', 'struktural'),

-- Jabatan Pelaksana
('JPL-PENGADMIN-UMUM', 'Pengadministrasi Umum', 'pelaksana'),
('JPL-PENGOLAH-DATA', 'Pengolah Data', 'pelaksana'),
('JPL-BENDAHARA', 'Bendahara', 'pelaksana'),
('JPL-SEKRETARIS', 'Sekretaris', 'pelaksana'),
('JPL-PRAMUBAKTI', 'Pramubakti', 'pelaksana'),
('JPL-PENGEMUDI', 'Pengemudi', 'pelaksana'),
('JPL-PETUGAS-KEAMANAN', 'Petugas Keamanan', 'pelaksana')

ON CONFLICT (kode) DO NOTHING;

-- ============================================
-- UPDATE PROFILES TABLE — Add new columns
-- ============================================
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS nip VARCHAR(30);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS golongan VARCHAR(10);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS jabatan_id INTEGER REFERENCES jabatan(id);

-- ============================================
-- GOLONGAN ASN — Reference list (not a table, used as dropdown values)
-- Format: I/a, I/b, I/c, I/d, II/a, II/b, II/c, II/d, III/a, III/b, III/c, III/d, IV/a, IV/b, IV/c, IV/d, IV/e
-- ============================================

-- ============================================
-- MENU SEED: Manajemen Jabatan for SUPERADMIN
-- (use WHERE NOT EXISTS to avoid ON CONFLICT NULL issues)
-- ============================================
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order, is_active, is_visible)
SELECT m.id, 'SUPERADMIN', 'Manajemen Jabatan', 'briefcase', '../admin/jabatan-management.php', 'jabatan-management', 5, TRUE, TRUE
FROM menus m 
WHERE m.label = 'Pengaturan Sistem' AND m.role_code = 'SUPERADMIN' AND m.parent_id IS NULL
AND NOT EXISTS (
    SELECT 1 FROM menus m2 
    WHERE m2.parent_id = m.id AND m2.role_code = 'SUPERADMIN' AND m2.label = 'Manajemen Jabatan'
)
LIMIT 1;

-- ============================================
-- VERIFIKASI
-- ============================================
SELECT 'Jabatan created: ' || COUNT(*) || ' rows' FROM jabatan;
SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'profiles' AND column_name IN ('nip','golongan','jabatan_id','unit_kerja_id');
