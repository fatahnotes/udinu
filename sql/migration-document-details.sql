-- ============================================
-- MIGRASI: Add document_number and document_date to submission_files
-- Untuk menyimpan Nomor Dokumen dan Tanggal Dokumen pada setiap berkas
-- ============================================

-- 1. Tambah kolom document_number ke submission_files
ALTER TABLE submission_files 
ADD COLUMN IF NOT EXISTS document_number VARCHAR(100);

-- 2. Tambah kolom document_date ke submission_files
ALTER TABLE submission_files 
ADD COLUMN IF NOT EXISTS document_date DATE;

-- 3. Buat index untuk pencarian dokumen
CREATE INDEX IF NOT EXISTS idx_sf_document_number ON submission_files(document_number);
