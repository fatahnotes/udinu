-- ============================================================
-- MIGRATION: 7-TAHAP WORKFLOW UJIAN DINAS & UPKP
-- ============================================================
-- Migrasi ini mengubah sistem dari 6 status sederhana menjadi
-- 7-tahap workflow yang lengkap:
--
-- Tahap 1: Pendaftaran    → status: 'draft', 'submitted'
-- Tahap 2: Verifikasi Satker → status: 'verified_satker', 'rejected_satker'
-- Tahap 3: Verifikasi Pusat  → status: 'verified_pusat', 'rejected_pusat'
-- Tahap 4: Masa Ujian        → status: 'exam_phase'
-- Tahap 5: Masa Penilaian    → status: 'scoring_phase'
-- Tahap 6: Pengumuman        → status: 'announced'
-- Tahap 7: Sertifikat        → status: 'certified'
--
-- Status akhir: 'passed' (lulus), 'not_passed' (tidak lulus)
-- ============================================================

BEGIN;

-- ============================================================
-- 1. ALTER submissions — Ubah CHECK constraint ke 7-stage
-- ============================================================

-- Hapus constraint lama
ALTER TABLE submissions DROP CONSTRAINT IF EXISTS submissions_status_check;

-- Tambah constraint baru dengan 7-tahap status
ALTER TABLE submissions ADD CONSTRAINT submissions_status_check 
CHECK (status IN (
    'draft',              -- Tahap 1: Draft pendaftaran
    'submitted',          -- Tahap 1: Submit final
    'verified_satker',    -- Tahap 2: Lulus verifikasi satker
    'rejected_satker',    -- Tahap 2: Ditolak satker (bisa revisi)
    'verified_pusat',     -- Tahap 3: Lulus verifikasi pusat
    'rejected_pusat',     -- Tahap 3: Ditolak pusat (final)
    'exam_phase',         -- Tahap 4: Masa ujian
    'scoring_phase',      -- Tahap 5: Masa penilaian
    'announced',          -- Tahap 6: Pengumuman hasil
    'certified',          -- Tahap 7: Sertifikat diterbitkan
    'passed',             -- Final: Lulus
    'not_passed'          -- Final: Tidak lulus
));

-- ============================================================
-- 2. ADD new columns to submissions
-- ============================================================

-- Verifikasi Satker
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS satker_verified_at TIMESTAMP;
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS satker_verified_by INTEGER REFERENCES users(id);
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS satker_notes TEXT;

-- Verifikasi Pusat
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS pusat_verified_at TIMESTAMP;
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS pusat_verified_by INTEGER REFERENCES users(id);
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS pusat_notes TEXT;

-- Masa Ujian
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS exam_participated BOOLEAN DEFAULT FALSE;
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS exam_date DATE;

-- Hasil Final
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS final_result VARCHAR(20);
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS final_score DECIMAL(6,2);
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS announcement_date TIMESTAMP;

-- Formasi (jika belum ada)
ALTER TABLE submissions ADD COLUMN IF NOT EXISTS formation_id INTEGER;

-- Indexes baru
CREATE INDEX IF NOT EXISTS idx_submissions_status_new ON submissions(status);
CREATE INDEX IF NOT EXISTS idx_submissions_satker_verified ON submissions(satker_verified_by);
CREATE INDEX IF NOT EXISTS idx_submissions_pusat_verified ON submissions(pusat_verified_by);
CREATE INDEX IF NOT EXISTS idx_submissions_final_result ON submissions(final_result);

-- ============================================================
-- 3. CREATE TABLE: status_history
-- Tracker history perubahan status (audit trail detail)
-- ============================================================
CREATE TABLE IF NOT EXISTS status_history (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER NOT NULL REFERENCES submissions(id) ON DELETE CASCADE,
    old_status VARCHAR(20),
    new_status VARCHAR(20) NOT NULL,
    changed_by INTEGER REFERENCES users(id),
    notes TEXT,
    ip_address INET,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_status_history_submission ON status_history(submission_id);
CREATE INDEX IF NOT EXISTS idx_status_history_created ON status_history(created_at);

-- ============================================================
-- 4. CREATE TABLE: exam_scores (Nilai dari Penyelenggara Ujian)
-- Import dari Excel — nilai ujian eksternal (CAT, dll)
-- ============================================================
CREATE TABLE IF NOT EXISTS exam_scores (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER NOT NULL REFERENCES submissions(id) ON DELETE CASCADE,
    exam_type VARCHAR(50) NOT NULL DEFAULT 'CAT',
    
    -- Nilai-nilai dari penyelenggara
    score_twk DECIMAL(5,2),       -- Tes Wawasan Kebangsaan
    score_tiu DECIMAL(5,2),       -- Tes Intelegensi Umum
    score_tkp DECIMAL(5,2),       -- Tes Karakteristik Pribadi
    score_tsi DECIMAL(5,2),       -- Tes Substansi Instansi (opsional)
    score_makalah DECIMAL(5,2),   -- Nilai Makalah (untuk UD2)
    total_exam_score DECIMAL(6,2) GENERATED ALWAYS AS (
        COALESCE(score_twk, 0) + COALESCE(score_tiu, 0) + 
        COALESCE(score_tkp, 0) + COALESCE(score_tsi, 0) + 
        COALESCE(score_makalah, 0)
    ) STORED,
    
    -- Status import
    import_batch VARCHAR(100),    -- Batch ID untuk tracking import
    imported_by INTEGER REFERENCES users(id),
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Notes
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(submission_id)
);

CREATE INDEX IF NOT EXISTS idx_exam_scores_submission ON exam_scores(submission_id);
CREATE INDEX IF NOT EXISTS idx_exam_scores_import_batch ON exam_scores(import_batch);

-- ============================================================
-- 5. CREATE TABLE: assessor_scores (Nilai dari Asesor Internal)
-- ============================================================
CREATE TABLE IF NOT EXISTS assessor_scores (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER NOT NULL REFERENCES submissions(id) ON DELETE CASCADE,
    assessor_id INTEGER NOT NULL REFERENCES users(id),
    
    -- Kriteria Penilaian Asesor
    criteria_relevansi DECIMAL(5,2) CHECK (criteria_relevansi >= 0 AND criteria_relevansi <= 100),
    criteria_kelengkapan DECIMAL(5,2) CHECK (criteria_kelengkapan >= 0 AND criteria_kelengkapan <= 100),
    criteria_kualitas DECIMAL(5,2) CHECK (criteria_kualitas >= 0 AND criteria_kualitas <= 100),
    criteria_originalitas DECIMAL(5,2) CHECK (criteria_originalitas >= 0 AND criteria_originalitas <= 100),
    criteria_presentasi DECIMAL(5,2) CHECK (criteria_presentasi >= 0 AND criteria_presentasi <= 100),
    
    total_assessor_score DECIMAL(6,2) GENERATED ALWAYS AS (
        (COALESCE(criteria_relevansi, 0) + 
         COALESCE(criteria_kelengkapan, 0) + 
         COALESCE(criteria_kualitas, 0) + 
         COALESCE(criteria_originalitas, 0) + 
         COALESCE(criteria_presentasi, 0)) / 5.0
    ) STORED,
    
    notes TEXT,
    scored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(submission_id, assessor_id)
);

CREATE INDEX IF NOT EXISTS idx_assessor_scores_submission ON assessor_scores(submission_id);
CREATE INDEX IF NOT EXISTS idx_assessor_scores_assessor ON assessor_scores(assessor_id);

-- ============================================================
-- 6. CREATE TABLE: final_scores (Penggabungan Nilai Akhir)
-- ============================================================
CREATE TABLE IF NOT EXISTS final_scores (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER NOT NULL UNIQUE REFERENCES submissions(id) ON DELETE CASCADE,
    vacancy_id INTEGER REFERENCES vacancies(id),
    
    -- Bobot (dalam persen, total = 100%)
    weight_exam DECIMAL(5,2) DEFAULT 60.00,     -- Bobot nilai ujian (default 60%)
    weight_assessor DECIMAL(5,2) DEFAULT 40.00,  -- Bobot nilai asesor (default 40%)
    
    -- Nilai Gabungan (computed in application, stored directly)
    exam_score_avg DECIMAL(6,2),     -- Rata-rata nilai dari penyelenggara
    assessor_score_avg DECIMAL(6,2), -- Rata-rata nilai dari semua asesor
    final_score DECIMAL(6,2),        -- Nilai akhir (dihitung aplikasi)
    
    -- Passing grade
    passing_grade DECIMAL(5,2) DEFAULT 70.00,   -- Nilai minimal kelulusan
    is_passed BOOLEAN,              -- Hasil kelulusan (dihitung aplikasi)
    
    calculated_by INTEGER REFERENCES users(id),
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT
);

CREATE INDEX IF NOT EXISTS idx_final_scores_vacancy ON final_scores(vacancy_id);
CREATE INDEX IF NOT EXISTS idx_final_scores_passed ON final_scores(is_passed);

-- ============================================================
-- 7. CREATE TABLE: certificates (Sertifikat Digital)
-- ============================================================
CREATE TABLE IF NOT EXISTS certificates (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER NOT NULL UNIQUE REFERENCES submissions(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id),
    vacancy_id INTEGER NOT NULL REFERENCES vacancies(id),
    
    -- Data Sertifikat
    certificate_number VARCHAR(100) UNIQUE NOT NULL,
    certificate_title VARCHAR(255),
    issued_date DATE DEFAULT CURRENT_DATE,
    expiry_date DATE,
    
    -- QR Code
    qr_code_data TEXT,              -- Data yang di-encode dalam QR
    qr_code_image VARCHAR(500),     -- Path gambar QR Code
    
    -- File Sertifikat
    certificate_file VARCHAR(500),  -- Path file PDF sertifikat
    file_size INTEGER,
    
    -- Status
    is_valid BOOLEAN DEFAULT TRUE,
    is_downloaded BOOLEAN DEFAULT FALSE,
    download_count INTEGER DEFAULT 0,
    
    -- Verifikasi
    verification_code VARCHAR(64) UNIQUE,  -- Kode verifikasi unik (SHA256)
    
    issued_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_certificates_user ON certificates(user_id);
CREATE INDEX IF NOT EXISTS idx_certificates_vacancy ON certificates(vacancy_id);
CREATE INDEX IF NOT EXISTS idx_certificates_verification ON certificates(verification_code);

-- ============================================================
-- 8. CREATE TABLE: recap_exam (Rekap Kepesertaan per Jenis Ujian)
-- ============================================================
CREATE TABLE IF NOT EXISTS recap_exam (
    id SERIAL PRIMARY KEY,
    vacancy_id INTEGER NOT NULL REFERENCES vacancies(id) ON DELETE CASCADE,
    vacancy_type_id INTEGER REFERENCES vacancy_types(id),
    
    -- Periode Ujian
    tahun_angkatan INTEGER NOT NULL,
    periode VARCHAR(100),
    
    -- Statistik
    total_registered INTEGER DEFAULT 0,     -- Total pendaftar
    total_verified_satker INTEGER DEFAULT 0, -- Lulus verifikasi satker
    total_verified_pusat INTEGER DEFAULT 0,  -- Lulus verifikasi pusat
    total_participated INTEGER DEFAULT 0,    -- Ikut ujian
    total_passed INTEGER DEFAULT 0,          -- Lulus
    total_not_passed INTEGER DEFAULT 0,      -- Tidak lulus
    
    -- File Rekap
    recap_file VARCHAR(500),         -- Path file rekap (PDF/Excel)
    recap_data JSONB,                -- Data rekap dalam format JSON
    
    -- Status
    is_finalized BOOLEAN DEFAULT FALSE,
    is_downloaded BOOLEAN DEFAULT FALSE,
    
    generated_by INTEGER REFERENCES users(id),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    UNIQUE(vacancy_id)
);

CREATE INDEX IF NOT EXISTS idx_recap_exam_vacancy ON recap_exam(vacancy_id);
CREATE INDEX IF NOT EXISTS idx_recap_exam_tahun ON recap_exam(tahun_angkatan);

-- ============================================================
-- 9. CREATE TABLE: formation (Formasi dalam ujian)
-- ============================================================
CREATE TABLE IF NOT EXISTS vacancy_formations (
    id SERIAL PRIMARY KEY,
    vacancy_id INTEGER NOT NULL REFERENCES vacancies(id) ON DELETE CASCADE,
    formation_name VARCHAR(255) NOT NULL,
    formation_type VARCHAR(50),
    quota INTEGER,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_vacancy_formations_vacancy ON vacancy_formations(vacancy_id);

-- ============================================================
-- 10. UPDATE status lama ke status baru (jika ada data existing)
-- ============================================================

-- verified → verified_satker (tahap awal verifikasi adalah satker)
UPDATE submissions SET status = 'verified_satker' WHERE status = 'verified';
-- scored → scoring_phase
UPDATE submissions SET status = 'scoring_phase' WHERE status = 'scored';
-- accepted → passed
UPDATE submissions SET status = 'passed', final_result = 'passed' WHERE status = 'accepted';
-- rejected → not_passed
UPDATE submissions SET status = 'not_passed', final_result = 'not_passed' WHERE status = 'rejected';

-- ============================================================
-- 11. FUNCTION: record_status_change()
-- Trigger function untuk mencatat perubahan status
-- ============================================================
CREATE OR REPLACE FUNCTION record_status_change()
RETURNS TRIGGER AS $$
BEGIN
    IF OLD.status IS DISTINCT FROM NEW.status THEN
        INSERT INTO status_history (submission_id, old_status, new_status, changed_by, notes)
        VALUES (NEW.id, OLD.status, NEW.status, 
                NULL,  -- Will be set by application layer
                'Status berubah dari ' || COALESCE(OLD.status, 'NULL') || ' ke ' || NEW.status);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply trigger
DROP TRIGGER IF EXISTS trigger_status_change ON submissions;
CREATE TRIGGER trigger_status_change
    AFTER UPDATE ON submissions
    FOR EACH ROW
    EXECUTE FUNCTION record_status_change();

-- ============================================================
-- 12. AUTO-ASSIGN Admin Satker (berdasarkan unit_kerja pendaftar)
-- ============================================================
CREATE OR REPLACE FUNCTION auto_assign_satker_verifikator()
RETURNS TRIGGER AS $$
DECLARE
    v_unit_kerja_id INTEGER;
    v_verifikator_id INTEGER;
BEGIN
    -- Only for newly submitted applications
    IF NEW.status = 'submitted' AND OLD.status = 'draft' THEN
        -- Get user's unit_kerja
        SELECT p.unit_kerja_id INTO v_unit_kerja_id 
        FROM profiles p WHERE p.user_id = NEW.user_id;
        
        -- Find assigned verifikator for this unit_kerja
        IF v_unit_kerja_id IS NOT NULL THEN
            SELECT ukv.user_id INTO v_verifikator_id
            FROM unit_kerja_verifikator ukv
            WHERE ukv.unit_kerja_id = v_unit_kerja_id
            LIMIT 1;
        END IF;
        
        -- Log assignment (actual assignment will be managed by the application)
        IF v_verifikator_id IS NOT NULL THEN
            INSERT INTO status_history (submission_id, old_status, new_status, changed_by, notes)
            VALUES (NEW.id, OLD.status, 'pending_satker', v_verifikator_id, 
                    'Auto-assigned ke verifikator satker ID: ' || v_verifikator_id);
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_auto_assign_satker ON submissions;
CREATE TRIGGER trigger_auto_assign_satker
    AFTER UPDATE ON submissions
    FOR EACH ROW
    EXECUTE FUNCTION auto_assign_satker_verifikator();

-- ============================================================
-- 13. Update vacancy_types — tambah kolom passing_grade default
-- ============================================================
ALTER TABLE vacancy_types ADD COLUMN IF NOT EXISTS passing_grade DECIMAL(5,2) DEFAULT 70.00;
ALTER TABLE vacancy_types ADD COLUMN IF NOT EXISTS exam_weight DECIMAL(5,2) DEFAULT 60.00;
ALTER TABLE vacancy_types ADD COLUMN IF NOT EXISTS assessor_weight DECIMAL(5,2) DEFAULT 40.00;

COMMIT;

-- ============================================================
-- VERIFICATION QUERY (jalankan setelah migration)
-- ============================================================
-- SELECT status, COUNT(*) FROM submissions GROUP BY status ORDER BY status;
-- SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('status_history','exam_scores','assessor_scores','final_scores','certificates','recap_exam','vacancy_formations');
