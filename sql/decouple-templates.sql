-- ============================================
-- MIGRASI: Decouple Documents & Requirements from Vacancy
-- Allows managing default templates per exam TYPE
-- ============================================

-- 1. Add vacancy_type_id to vacancy_documents
ALTER TABLE vacancy_documents 
ADD COLUMN IF NOT EXISTS vacancy_type_id INTEGER REFERENCES vacancy_types(id);

-- 2. Add vacancy_type_id to vacancy_requirements
ALTER TABLE vacancy_requirements 
ADD COLUMN IF NOT EXISTS vacancy_type_id INTEGER REFERENCES vacancy_types(id);

-- 3. Make vacancy_id nullable (NULL = template, NOT NULL = per-vacancy)
ALTER TABLE vacancy_documents ALTER COLUMN vacancy_id DROP NOT NULL;
ALTER TABLE vacancy_requirements ALTER COLUMN vacancy_id DROP NOT NULL;

-- 4. Create indexes for type-based queries
CREATE INDEX IF NOT EXISTS idx_vd_type ON vacancy_documents(vacancy_type_id);
CREATE INDEX IF NOT EXISTS idx_vr_type ON vacancy_requirements(vacancy_type_id);
CREATE INDEX IF NOT EXISTS idx_vd_type_vacancy ON vacancy_documents(vacancy_type_id, vacancy_id);
CREATE INDEX IF NOT EXISTS idx_vr_type_vacancy ON vacancy_requirements(vacancy_type_id, vacancy_id);

-- 5. Migrate existing data: set vacancy_type_id from parent vacancy
UPDATE vacancy_documents vd 
SET vacancy_type_id = v.vacancy_type_id 
FROM vacancies v 
WHERE vd.vacancy_id = v.id AND vd.vacancy_type_id IS NULL;

UPDATE vacancy_requirements vr 
SET vacancy_type_id = v.vacancy_type_id 
FROM vacancies v 
WHERE vr.vacancy_id = v.id AND vr.vacancy_type_id IS NULL;

-- 6. Insert default templates for UD1 (if not exists)
INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'Surat Usulan Pimpinan', 'surat_usulan', TRUE, 1
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'SK Pangkat II/d Terakhir', 'sk_pangkat', TRUE, 2
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 2);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'Penilaian Prestasi Kerja 2 Tahun', 'ppk', TRUE, 3
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 3);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'KTP Elektronik', 'ktp', TRUE, 4
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 4);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'Kartu Pegawai / NIP', 'karpeg', TRUE, 5
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 5);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'Ijazah Terakhir (Legalized)', 'ijazah', TRUE, 6
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 6);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'Surat Keterangan Sehat', 'surat_sehat', TRUE, 7
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 7);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'Pas Foto 4x6 Latar Merah', 'pas_foto', TRUE, 8
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 8);

INSERT INTO vacancy_documents (vacancy_type_id, document_name, document_code, is_required, display_order)
SELECT vt.id, 'Surat Pernyataan Tidak Sedang Diberhentikan', 'surat_pernyataan', TRUE, 9
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_documents WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 9);

-- Insert UD1 requirements templates
INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'umum', 'Pegawai Negeri Sipil di lingkungan Kemdiktisaintek', 'radio', TRUE, '{"options": ["Ya", "Tidak"]}', 1
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 1);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'umum', 'Memiliki pangkat Pengatur Tingkat I / golongan ruang II/d', 'validation', TRUE, NULL, 2
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 2);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'umum', 'Telah 1 tahun dalam pangkat/golongan ruang II/d', 'validation', TRUE, NULL, 3
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 3);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'umum', 'Penilaian Prestasi Kerja 2 tahun terakhir minimal Baik', 'file', TRUE, NULL, 4
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 4);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'umum', 'SK Kenaikan Pangkat II/d terakhir', 'file', TRUE, NULL, 5
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 5);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'umum', 'Tidak sedang diberhentikan sementara / menerima uang tunggu / cuti di luar tanggungan negara', 'radio', TRUE, '{"options": ["Ya (Tidak Sedang)", "Tidak"]}', 6
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 6);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'umum', 'Diusulkan oleh Pejabat Pimpinan Tinggi Pratama', 'file', TRUE, NULL, 7
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 7);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'khusus', 'Surat Keterangan Sehat Jasmani dan Rohani', 'file', TRUE, NULL, 8
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 8);

INSERT INTO vacancy_requirements (vacancy_type_id, requirement_type, requirement_text, input_type, is_required, options, display_order)
SELECT vt.id, 'khusus', 'Pas foto terbaru latar merah ukuran 4x6', 'file', TRUE, NULL, 9
FROM vacancy_types vt WHERE vt.type_code = 'UD1'
AND NOT EXISTS (SELECT 1 FROM vacancy_requirements WHERE vacancy_type_id = vt.id AND vacancy_id IS NULL AND display_order = 9);

-- 7. Verifikasi
SELECT 'Templates (no vacancy):' as info;
SELECT 'Documents: ' || COUNT(*) FROM vacancy_documents WHERE vacancy_id IS NULL;
SELECT 'Requirements: ' || COUNT(*) FROM vacancy_requirements WHERE vacancy_id IS NULL;
