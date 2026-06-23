-- ============================================
-- UNIT KERJA & Admin Pusat MAPPING
-- ============================================

-- Unit Kerja / Satuan Kerja (Satker) table
CREATE TABLE IF NOT EXISTS unit_kerja (
    id SERIAL PRIMARY KEY,
    kode_satker VARCHAR(20) UNIQUE NOT NULL,
    nama_satker VARCHAR(255) NOT NULL,
    parent_id INTEGER DEFAULT NULL REFERENCES unit_kerja(id) ON DELETE SET NULL,
    level VARCHAR(50) DEFAULT 'satker',
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_unit_kerja_parent ON unit_kerja(parent_id);
CREATE INDEX idx_unit_kerja_kode ON unit_kerja(kode_satker);

-- Add unit_kerja_id to profiles table (for scoping Admin Pusat access)
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS unit_kerja_id INTEGER REFERENCES unit_kerja(id) ON DELETE SET NULL;
CREATE INDEX IF NOT EXISTS idx_profiles_unit_kerja ON profiles(unit_kerja_id);

-- Mapping Admin Pusat ke Unit Kerja
-- Seorang ADMIN_PUSAT hanya bisa memverifikasi peserta dari unit_kerja yang ditugaskan
CREATE TABLE IF NOT EXISTS unit_kerja_Admin Pusat (
    id SERIAL PRIMARY KEY,
    unit_kerja_id INTEGER NOT NULL REFERENCES unit_kerja(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    assigned_by INTEGER REFERENCES users(id),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(unit_kerja_id, user_id)
);

CREATE INDEX idx_ukv_unit ON unit_kerja_Admin Pusat(unit_kerja_id);
CREATE INDEX idx_ukv_user ON unit_kerja_Admin Pusat(user_id);

-- ============================================
-- SEED: Menu untuk Unit Kerja (SUPERADMIN)
-- ============================================
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order, is_active, is_visible) VALUES
(
    (SELECT id FROM menus WHERE label = 'Pengaturan Sistem' AND role_code = 'SUPERADMIN' AND parent_id IS NULL LIMIT 1),
    'SUPERADMIN',
    'Manajemen Unit Kerja',
    'sitemap',
    '../admin/unit-kerja.php',
    'unit-kerja',
    4,
    TRUE,
    TRUE
);

-- ============================================
-- SEED: Unit Kerja contoh
-- ============================================
INSERT INTO unit_kerja (kode_satker, nama_satker, level, alamat, is_active) VALUES
('SETJEN', 'Sekretariat Jenderal', 'eselon1', 'Jl. Jenderal Sudirman, Jakarta', TRUE),
('DITJEN-DIKTI', 'Direktorat Jenderal Pendidikan Tinggi', 'eselon1', 'Jl. Jenderal Sudirman, Jakarta', TRUE),
('DITJEN-VOKASI', 'Direktorat Jenderal Pendidikan Vokasi', 'eselon1', 'Jl. Jenderal Sudirman, Jakarta', TRUE),
('DITJEN-GTK', 'Direktorat Jenderal Guru dan Tenaga Kependidikan', 'eselon1', 'Jl. Jenderal Sudirman, Jakarta', TRUE),
('DITJEN-PAUD', 'Direktorat Jenderal PAUD, Dikdas, Dikmen', 'eselon1', 'Jl. Jenderal Sudirman, Jakarta', TRUE),
('DITJEN-BUDI', 'Direktorat Jenderal Kebudayaan', 'eselon1', 'Jl. Jenderal Sudirman, Jakarta', TRUE);

-- Jika ada ADMIN_PUSAT, assign ke unit kerja contoh
-- INSERT INTO unit_kerja_Admin Pusat (unit_kerja_id, user_id, assigned_by) VALUES (1, 2, 1);

-- ============================================
-- VERIFIKASI
-- ============================================
SELECT 'Unit Kerja table created: ' || COUNT(*) || ' rows' FROM unit_kerja;
SELECT 'Admin Pusat mappings: ' || COUNT(*) || ' rows' FROM unit_kerja_Admin Pusat;
