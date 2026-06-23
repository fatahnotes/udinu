-- ============================================
-- MENUS TABLE — Dynamic Menu System
-- ============================================
CREATE TABLE IF NOT EXISTS menus (
    id SERIAL PRIMARY KEY,
    parent_id INTEGER DEFAULT NULL REFERENCES menus(id) ON DELETE CASCADE,
    role_code VARCHAR(20) NOT NULL DEFAULT 'ALL',
    label VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'circle',
    url VARCHAR(255) DEFAULT '#',
    active_key VARCHAR(50) DEFAULT NULL,
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    is_visible BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_menus_parent ON menus(parent_id);
CREATE INDEX idx_menus_role ON menus(role_code);
CREATE INDEX idx_menus_order ON menus(display_order);

-- ============================================
-- SEED: Menu Hierarchy untuk Semua Role
-- ============================================

-- ========== SUPERADMIN ==========
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
-- Root menus
(NULL, 'SUPERADMIN', 'Dashboard', 'tachometer-alt', 'dashboard.php', 'dashboard', 1),
(NULL, 'SUPERADMIN', 'Manajemen Ujian', 'file-alt', '#', 'exam', 2),
(NULL, 'SUPERADMIN', 'Manajemen User', 'users', '../admin/user-management.php', 'user-management', 3),
(NULL, 'SUPERADMIN', 'Verifikasi & Seleksi', 'check-circle', '#', 'verification', 4),
(NULL, 'SUPERADMIN', 'Penilaian', 'star', '#', 'scoring', 5),
(NULL, 'SUPERADMIN', 'Pengumuman & Sertifikat', 'bullhorn', '#', 'announcement', 6),
(NULL, 'SUPERADMIN', 'Pengaturan Sistem', 'cog', '#', 'settings', 7);

-- Sub: Manajemen Ujian
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(2, 'SUPERADMIN', 'Daftar Ujian', 'list', '../admin/vacancy-management.php', 'vacancy-management', 1),
(2, 'SUPERADMIN', 'Master Data Ujian', 'database', '../admin/exam-master.php', 'exam-master', 2),
(2, 'SUPERADMIN', 'Rekap Pendaftar', 'clipboard-list', '../admin/exam-applicants.php', 'exam-applicants', 3);

-- Sub: Verifikasi & Seleksi
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(4, 'SUPERADMIN', 'Verifikasi Berkas', 'check-double', '../verification/verification.php', 'verification', 1),
(4, 'SUPERADMIN', 'Daftar Pendaftar', 'users', '../verification/applicant-list.php', 'applicant-list', 2),
(4, 'SUPERADMIN', 'Dokumen Pendukung', 'folder-open', '../verification/documents.php', 'verification-docs', 3);

-- Sub: Penilaian
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(5, 'SUPERADMIN', 'Input Nilai', 'pen', '../scoring/scoring.php', 'scoring', 1),
(5, 'SUPERADMIN', 'Kelola Kriteria', 'sliders-h', '../scoring/criteria.php', 'scoring-criteria', 2),
(5, 'SUPERADMIN', 'Hasil Penilaian', 'chart-bar', '../scoring/results.php', 'scoring-results', 3);

-- Sub: Pengumuman & Sertifikat
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(6, 'SUPERADMIN', 'Buat Pengumuman', 'envelope', '../announcement/announcement.php', 'announcement', 1),
(6, 'SUPERADMIN', 'Kelulusan', 'graduation-cap', '../announcement/graduation.php', 'graduation', 2),
(6, 'SUPERADMIN', 'Sertifikat', 'certificate', '../announcement/certificate.php', 'certificate', 3);

-- Sub: Pengaturan Sistem
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(7, 'SUPERADMIN', 'Konfigurasi Umum', 'wrench', '../admin/configuration.php', 'configuration', 1),
(7, 'SUPERADMIN', 'Manajemen Menu', 'bars', '../admin/menu-management.php', 'menu-management', 2),
(7, 'SUPERADMIN', 'Log Aktivitas', 'history', '../admin/audit-log.php', 'audit-log', 3);

-- ========== ADMIN_PUSAT ==========
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(NULL, 'ADMIN_PUSAT', 'Dashboard', 'tachometer-alt', 'dashboard.php', 'dashboard', 1),
(NULL, 'ADMIN_PUSAT', 'Verifikasi Berkas', 'check-circle', '#', 'verification', 2),
(NULL, 'ADMIN_PUSAT', 'Rekap & Laporan', 'chart-bar', '#', 'reports', 3);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Verifikasi Berkas' AND role_code='ADMIN_PUSAT'), 'ADMIN_PUSAT', 'Verifikasi Dokumen', 'check-double', '../verification/verification.php', 'verification', 1),
((SELECT id FROM menus WHERE label='Verifikasi Berkas' AND role_code='ADMIN_PUSAT'), 'ADMIN_PUSAT', 'Daftar Pendaftar', 'users', '../verification/applicant-list.php', 'applicant-list', 2);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Rekap & Laporan' AND role_code='ADMIN_PUSAT'), 'ADMIN_PUSAT', 'Statistik', 'chart-pie', '../verification/statistics.php', 'statistics', 1),
((SELECT id FROM menus WHERE label='Rekap & Laporan' AND role_code='ADMIN_PUSAT'), 'ADMIN_PUSAT', 'Rekap Harian', 'calendar-check', '../verification/daily-report.php', 'daily-report', 2);

-- ========== ADMIN_SATKER ==========
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(NULL, 'ADMIN_SATKER', 'Dashboard', 'tachometer-alt', 'dashboard.php', 'dashboard', 1),
(NULL, 'ADMIN_SATKER', 'Satker Saya', 'building', '#', 'my-satker', 2),
(NULL, 'ADMIN_SATKER', 'Verifikasi Pendaftar', 'check-circle', '#', 'verification-satker', 3),
(NULL, 'ADMIN_SATKER', 'Laporan', 'chart-bar', '#', 'reports', 4);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Satker Saya' AND role_code='ADMIN_SATKER'), 'ADMIN_SATKER', 'Lihat Unit Kerja', 'sitemap', '../admin/unit-kerja.php?tab=myunits', 'my-units', 1);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Verifikasi Pendaftar' AND role_code='ADMIN_SATKER'), 'ADMIN_SATKER', 'Verifikasi Berkas', 'check-double', '../verification/verification.php', 'verification', 1),
((SELECT id FROM menus WHERE label='Verifikasi Pendaftar' AND role_code='ADMIN_SATKER'), 'ADMIN_SATKER', 'Daftar Pendaftar (Satker)', 'users', '../verification/applicant-list.php', 'applicant-list', 2);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Laporan' AND role_code='ADMIN_SATKER'), 'ADMIN_SATKER', 'Statistik Satker', 'chart-pie', '../verification/statistics.php', 'statistics', 1);

-- ========== ASSESSOR ==========
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(NULL, 'ASSESSOR', 'Dashboard', 'tachometer-alt', 'dashboard.php', 'dashboard', 1),
(NULL, 'ASSESSOR', 'Penilaian', 'star', '#', 'scoring', 2);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Penilaian' AND role_code='ASSESSOR'), 'ASSESSOR', 'Input Nilai', 'pen', '../scoring/scoring.php', 'scoring', 1),
((SELECT id FROM menus WHERE label='Penilaian' AND role_code='ASSESSOR'), 'ASSESSOR', 'Hasil Penilaian', 'clipboard-check', '../scoring/results.php', 'scoring-results', 2),
((SELECT id FROM menus WHERE label='Penilaian' AND role_code='ASSESSOR'), 'ASSESSOR', 'Analisis', 'chart-line', '../scoring/analysis.php', 'scoring-analysis', 3);

-- ========== USER (Pendaftar) ==========
INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
(NULL, 'USER', 'Dashboard', 'tachometer-alt', 'dashboard.php', 'dashboard', 1),
(NULL, 'USER', 'Profil Saya', 'user', '#', 'profile', 2),
(NULL, 'USER', 'Pendaftaran Ujian', 'file-alt', '#', 'submission', 3),
(NULL, 'USER', 'Status & Hasil', 'history', '#', 'status', 4);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Profil Saya' AND role_code='USER'), 'USER', 'Edit Profil', 'user-edit', '../profile/profile.php', 'profile', 1),
((SELECT id FROM menus WHERE label='Profil Saya' AND role_code='USER'), 'USER', 'Dokumen Saya', 'folder', '../profile/documents.php', 'profile-docs', 2);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Pendaftaran Ujian' AND role_code='USER'), 'USER', 'Daftar Ujian Baru', 'plus-circle', '../submission/submission.php', 'submission', 1),
((SELECT id FROM menus WHERE label='Pendaftaran Ujian' AND role_code='USER'), 'USER', 'Riwayat Pendaftaran', 'list', '../submission/history.php', 'submission-history', 2);

INSERT INTO menus (parent_id, role_code, label, icon, url, active_key, display_order) VALUES
((SELECT id FROM menus WHERE label='Status & Hasil' AND role_code='USER'), 'USER', 'Status Proses', 'tasks', '../submission/status.php', 'status', 1),
((SELECT id FROM menus WHERE label='Status & Hasil' AND role_code='USER'), 'USER', 'Pengumuman', 'bullhorn', '../announcement/view.php', 'announcement-view', 2),
((SELECT id FROM menus WHERE label='Status & Hasil' AND role_code='USER'), 'USER', 'Sertifikat', 'certificate', '../announcement/certificate-view.php', 'certificate-view', 3);

-- Verifikasi hasil
SELECT 
    m.id, COALESCE(p.label, '—') as parent, m.label, m.role_code, m.display_order
FROM menus m
LEFT JOIN menus p ON m.parent_id = p.id
ORDER BY m.role_code, COALESCE(m.parent_id, 0), m.display_order;
