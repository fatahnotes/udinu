-- Migration: Add education history & training tables
-- Run: psql -U postgres -d ujian_dinas2026 -f sql/migration-education-training.sql

-- User education history (multiple entries per user)
CREATE TABLE IF NOT EXISTS user_education (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    level VARCHAR(50) NOT NULL,
    major VARCHAR(100),
    degree VARCHAR(50),
    institution VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_user_education_user ON user_education(user_id);

-- User training/pelatihan (multiple entries per user)
CREATE TABLE IF NOT EXISTS user_training (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    training_name VARCHAR(255) NOT NULL,
    organizer VARCHAR(100),
    training_year INTEGER,
    certificate VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_user_training_user ON user_training(user_id);
