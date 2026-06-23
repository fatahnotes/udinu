-- ============================================
-- MIGRASI: Manajemen Lowongan → Manajemen Ujian
-- Database: ujian_dinas2026
-- ============================================

-- 1. Update vacancy_types: KPS→UD1, GP→UD2, TKD→UPKP
UPDATE vacancy_types SET 
    type_code = 'UD1',
    type_name = 'Ujian Dinas Tingkat I',
    description = 'Ujian bagi PNS pangkat Pengatur Tingkat I (II/d) naik ke Penata Muda (III/a). Materi: TWK, TKP, TSI (100 soal CAT).'
WHERE type_code = 'KPS';

UPDATE vacancy_types SET 
    type_code = 'UD2',
    type_name = 'Ujian Dinas Tingkat II',
    description = 'Ujian bagi PNS pangkat Penata Tingkat I (III/d) naik ke Pembina (IV/a). Materi: CAT + Makalah.'
WHERE type_code = 'GP';

UPDATE vacancy_types SET 
    type_code = 'UPKP',
    type_name = 'UPKP',
    description = 'Ujian Penyesuaian Kenaikan Pangkat bagi PNS dengan ijazah lebih tinggi dari jenjang pangkat saat ini.'
WHERE type_code = 'TKD';

-- 2. Hapus requirements & documents lama (Guru Garuda / KPS/GP/TKD)
DELETE FROM vacancy_requirements WHERE vacancy_id IN (11,12,13,14);
DELETE FROM vacancy_documents WHERE vacancy_id IN (11,12,13,14);
DELETE FROM vacancy_formations WHERE vacancy_id IN (11,12,13,14);

-- 3. Update vacancies yang lama menjadi nonaktif (data demo lama)
UPDATE vacancies SET 
    title = 'Ujian Dinas Tingkat I TA 2026',
    description = 'Ujian Dinas Tingkat I bagi PNS Kemdiktisaintek dari pangkat Pengatur Tingkat I (II/d) ke Penata Muda (III/a).\n\nMateri: TWK, TKP, TSI — 100 soal dengan metode CAT.\n\nPersyaratan:\n- PNS Kemdiktisaintek\n- Pangkat II/d minimal 1 tahun\n- PPK 2 tahun terakhir minimal Baik\n- Diusulkan Pimpinan Tinggi Pratama',
    tahun_angkatan = 2026,
    open_date = '2026-06-01',
    close_date = '2026-12-31',
    max_applicants = 500
WHERE id = 11;

UPDATE vacancies SET 
    title = 'Ujian Dinas Tingkat II TA 2026',
    description = 'Ujian Dinas Tingkat II bagi PNS Kemdiktisaintek dari pangkat Penata Tingkat I (III/d) ke Pembina (IV/a).\n\nMateri: CAT + Penilaian Makalah karya tulis ilmiah oleh Pejabat Pimpinan Tinggi Pratama.\n\nPersyaratan:\n- PNS Kemdiktisaintek\n- Pangkat III/d\n- Jabatan Struktural Administrator\n- Belum mengikuti PKA\n- Diusulkan Pimpinan Tinggi Pratama',
    tahun_angkatan = 2026,
    open_date = '2026-07-01',
    close_date = '2027-01-31',
    max_applicants = 300
WHERE id = 12;

UPDATE vacancies SET 
    title = 'UPKP TA 2026',
    description = 'Ujian Penyesuaian Kenaikan Pangkat bagi PNS Kemdiktisaintek yang memiliki ijazah lebih tinggi dari jenjang pangkat saat ini.\n\nPersyaratan:\n- PNS Kemdiktisaintek\n- Ijazah lebih tinggi (legalized)\n- Transkrip nilai\n- SK Tugas Belajar / SKMPLT (jika ada)\n- PPK 2 tahun terakhir minimal Baik\n- Diusulkan Pimpinan Tinggi Pratama',
    tahun_angkatan = 2026,
    open_date = '2026-08-01',
    close_date = '2027-02-28',
    max_applicants = 200
WHERE id = 13;

-- Nonaktifkan vacancy demo lama (tes kepala sekolah, dll)
UPDATE vacancies SET is_active = FALSE WHERE id IN (14, 15, 16);

-- 4. Insert default requirements untuk UD1 (vacancy_id = 11)
INSERT INTO vacancy_requirements (vacancy_id, requirement_type, requirement_text, input_type, is_required, options, display_order) VALUES
(11, 'umum', 'Pegawai Negeri Sipil di lingkungan Kemdiktisaintek', 'radio', TRUE, '{"options": ["Ya", "Tidak"]}', 1),
(11, 'umum', 'Memiliki pangkat Pengatur Tingkat I / golongan ruang II/d', 'validation', TRUE, NULL, 2),
(11, 'umum', 'Telah 1 (satu) tahun dalam pangkat/golongan ruang II/d', 'validation', TRUE, NULL, 3),
(11, 'umum', 'Penilaian Prestasi Kerja 2 tahun terakhir minimal Baik', 'file', TRUE, NULL, 4),
(11, 'umum', 'Surat Keputusan Kenaikan Pangkat II/d terakhir', 'file', TRUE, NULL, 5),
(11, 'umum', 'Tidak sedang diberhentikan sementara / menerima uang tunggu / cuti di luar tanggungan negara', 'radio', TRUE, '{"options": ["Ya (Tidak Sedang)", "Tidak"]}', 6),
(11, 'umum', 'Diusulkan oleh Pejabat Pimpinan Tinggi Pratama unit kerja', 'file', TRUE, NULL, 7),
(11, 'khusus', 'Surat Keterangan Sehat Jasmani dan Rohani dari Dokter Pemerintah', 'file', TRUE, NULL, 8),
(11, 'khusus', 'Pas foto terbaru latar merah ukuran 4x6', 'file', TRUE, NULL, 9);

-- 5. Insert default documents untuk UD1
INSERT INTO vacancy_documents (vacancy_id, document_name, document_code, is_required, display_order) VALUES
(11, 'Surat Usulan Pimpinan', 'surat_usulan', TRUE, 1),
(11, 'SK Pangkat II/d Terakhir', 'sk_pangkat', TRUE, 2),
(11, 'Penilaian Prestasi Kerja 2 Tahun', 'ppk', TRUE, 3),
(11, 'KTP Elektronik', 'ktp', TRUE, 4),
(11, 'Kartu Pegawai / NIP', 'karpeg', TRUE, 5),
(11, 'Ijazah Terakhir (Legalized)', 'ijazah', TRUE, 6),
(11, 'Surat Keterangan Sehat', 'surat_sehat', TRUE, 7),
(11, 'Pas Foto 4x6 Latar Merah', 'pas_foto', TRUE, 8),
(11, 'Surat Pernyataan Tidak Sedang Diberhentikan', 'surat_pernyataan', TRUE, 9);

-- 6. Insert default requirements untuk UD2 (vacancy_id = 12)
INSERT INTO vacancy_requirements (vacancy_id, requirement_type, requirement_text, input_type, is_required, options, display_order) VALUES
(12, 'umum', 'Pegawai Negeri Sipil di lingkungan Kemdiktisaintek', 'radio', TRUE, '{"options": ["Ya", "Tidak"]}', 1),
(12, 'umum', 'Memiliki pangkat Penata Tingkat I / golongan ruang III/d', 'validation', TRUE, NULL, 2),
(12, 'umum', 'Menduduki Jabatan Struktural Administrator', 'radio', TRUE, '{"options": ["Ya", "Tidak"]}', 3),
(12, 'umum', 'Belum mengikuti Pelatihan Kepemimpinan Administrator (PKA)', 'radio', TRUE, '{"options": ["Ya (Belum)", "Sudah"]}', 4),
(12, 'umum', 'Penilaian Prestasi Kerja 2 tahun terakhir minimal Baik', 'file', TRUE, NULL, 5),
(12, 'umum', 'Surat Keputusan Kenaikan Pangkat III/d terakhir', 'file', TRUE, NULL, 6),
(12, 'umum', 'Tidak sedang diberhentikan sementara / menerima uang tunggu / cuti di luar tanggungan negara', 'radio', TRUE, '{"options": ["Ya (Tidak Sedang)", "Tidak"]}', 7),
(12, 'umum', 'Diusulkan oleh Pejabat Pimpinan Tinggi Pratama unit kerja', 'file', TRUE, NULL, 8),
(12, 'khusus', 'Naskah Makalah karya tulis ilmiah sesuai TUPOKSI unit kerja', 'file', TRUE, NULL, 9),
(12, 'khusus', 'Surat Keterangan Sehat Jasmani dan Rohani dari Dokter Pemerintah', 'file', TRUE, NULL, 10),
(12, 'khusus', 'Pas foto terbaru latar merah ukuran 4x6', 'file', TRUE, NULL, 11);

-- 7. Insert default documents untuk UD2
INSERT INTO vacancy_documents (vacancy_id, document_name, document_code, is_required, display_order) VALUES
(12, 'Surat Usulan Pimpinan', 'surat_usulan', TRUE, 1),
(12, 'SK Pangkat III/d Terakhir', 'sk_pangkat', TRUE, 2),
(12, 'SK Jabatan Struktural Administrator', 'sk_jabatan', TRUE, 3),
(12, 'Penilaian Prestasi Kerja 2 Tahun', 'ppk', TRUE, 4),
(12, 'KTP Elektronik', 'ktp', TRUE, 5),
(12, 'Kartu Pegawai / NIP', 'karpeg', TRUE, 6),
(12, 'Ijazah Terakhir (Legalized)', 'ijazah', TRUE, 7),
(12, 'Naskah Makalah Karya Tulis Ilmiah', 'makalah', TRUE, 8),
(12, 'Surat Keterangan Sehat', 'surat_sehat', TRUE, 9),
(12, 'Pas Foto 4x6 Latar Merah', 'pas_foto', TRUE, 10),
(12, 'Surat Pernyataan Tidak Sedang Diberhentikan', 'surat_pernyataan', TRUE, 11);

-- 8. Insert default requirements untuk UPKP (vacancy_id = 13)
INSERT INTO vacancy_requirements (vacancy_id, requirement_type, requirement_text, input_type, is_required, options, display_order) VALUES
(13, 'umum', 'Pegawai Negeri Sipil di lingkungan Kemdiktisaintek', 'radio', TRUE, '{"options": ["Ya", "Tidak"]}', 1),
(13, 'umum', 'Telah memperoleh ijazah lebih tinggi dari jenjang pangkat dan golongan ruang saat ini', 'validation', TRUE, NULL, 2),
(13, 'umum', 'Ijazah dari sekolah/perguruan tinggi negeri atau swasta yang terakreditasi', 'file', TRUE, NULL, 3),
(13, 'umum', 'Surat Keterangan Memiliki Pendidikan Lebih Tinggi (SKMPLT) jika ada', 'file', FALSE, NULL, 4),
(13, 'umum', 'Surat Keputusan Tugas Belajar (SK Tugas Belajar) jika ada', 'file', FALSE, NULL, 5),
(13, 'umum', 'Penilaian Prestasi Kerja 2 tahun terakhir minimal Baik', 'file', TRUE, NULL, 6),
(13, 'umum', 'Diusulkan oleh Pejabat Pimpinan Tinggi Pratama unit kerja', 'file', TRUE, NULL, 7),
(13, 'khusus', 'Transkrip nilai ijazah terakhir', 'file', TRUE, NULL, 8),
(13, 'khusus', 'Surat Keterangan Sehat Jasmani dan Rohani dari Dokter Pemerintah', 'file', TRUE, NULL, 9),
(13, 'khusus', 'Pas foto terbaru latar merah ukuran 4x6', 'file', TRUE, NULL, 10);

-- 9. Insert default documents untuk UPKP
INSERT INTO vacancy_documents (vacancy_id, document_name, document_code, is_required, display_order) VALUES
(13, 'Surat Usulan Pimpinan', 'surat_usulan', TRUE, 1),
(13, 'Ijazah Baru yang Lebih Tinggi (Legalized)', 'ijazah_baru', TRUE, 2),
(13, 'Transkrip Nilai Ijazah Baru', 'transkrip', TRUE, 3),
(13, 'SK Tugas Belajar (jika ada)', 'sk_tugas_belajar', FALSE, 4),
(13, 'SKMPLT (jika ada)', 'skmplt', FALSE, 5),
(13, 'Penilaian Prestasi Kerja 2 Tahun', 'ppk', TRUE, 6),
(13, 'KTP Elektronik', 'ktp', TRUE, 7),
(13, 'Kartu Pegawai / NIP', 'karpeg', TRUE, 8),
(13, 'Surat Keterangan Sehat', 'surat_sehat', TRUE, 9),
(13, 'Pas Foto 4x6 Latar Merah', 'pas_foto', TRUE, 10);

-- ============================================
-- VERIFIKASI
-- ============================================
-- Cek hasil migrasi
SELECT id, type_code, type_name FROM vacancy_types ORDER BY id;
SELECT id, vacancy_code, title, is_active FROM vacancies ORDER BY id;
SELECT COUNT(*) as total_requirements FROM vacancy_requirements;
SELECT COUNT(*) as total_documents FROM vacancy_documents;
