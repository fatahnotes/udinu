-- Migration: Add optional profile enrichment columns
-- Run: psql -U postgres -d ujian_dinas2026 -f sql/migration-profile-enrich.sql

ALTER TABLE profiles ADD COLUMN IF NOT EXISTS nik VARCHAR(16);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS tempat_lahir VARCHAR(100);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS agama VARCHAR(20);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS status_perkawinan VARCHAR(20);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS npwp VARCHAR(20);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS no_karpeg VARCHAR(30);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS tmt_cpns DATE;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS tmt_pns DATE;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS bio TEXT;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS linkedin_url VARCHAR(255);
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS skills TEXT;
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS sertifikasi TEXT;
