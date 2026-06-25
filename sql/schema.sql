-- Database schema for Guru Garuda System

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Roles table
CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    role_code VARCHAR(20) UNIQUE NOT NULL,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    uuid UUID DEFAULT uuid_generate_v4() UNIQUE,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    full_name VARCHAR(255) NOT NULL,
    is_email_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP,
    login_attempts INTEGER DEFAULT 0,
    locked_until TIMESTAMP,
    must_change_password BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User roles junction table
CREATE TABLE user_roles (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    role_id INTEGER REFERENCES roles(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INTEGER REFERENCES users(id),
    PRIMARY KEY (user_id, role_id)
);

-- User profiles table
CREATE TABLE profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    nip VARCHAR(30),
    nik VARCHAR(16),
    gender VARCHAR(10),
    phone VARCHAR(20),
    address TEXT,
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    agama VARCHAR(20),
    status_perkawinan VARCHAR(20),
    golongan VARCHAR(10),
    jabatan_id INTEGER REFERENCES jabatan(id),
    unit_kerja_id INTEGER REFERENCES unit_kerja(id) ON DELETE SET NULL,
    status_pekerjaan VARCHAR(20),
    npwp VARCHAR(20),
    no_karpeg VARCHAR(30),
    tmt_cpns DATE,
    tmt_pns DATE,
    last_education VARCHAR(100),
    major VARCHAR(100),
    institution VARCHAR(255),
    foto VARCHAR(500),
    provinsi VARCHAR(100),
    linkedin_url VARCHAR(255),
    skills TEXT,
    sertifikasi TEXT,
    bio TEXT,
    is_profile_complete BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vacancies table
CREATE TABLE vacancies (
    id SERIAL PRIMARY KEY,
    vacancy_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    requirements TEXT,
    open_date DATE NOT NULL,
    close_date DATE NOT NULL,
    max_applicants INTEGER,
    current_applicants INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Submissions table
CREATE TABLE submissions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    vacancy_id INTEGER REFERENCES vacancies(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'submitted', 'verified', 'scored', 'rejected', 'accepted')),
    submission_date TIMESTAMP,
    verification_date TIMESTAMP,
    scoring_date TIMESTAMP,
    announcement_date TIMESTAMP,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, vacancy_id)
);

-- Submission files table
CREATE TABLE submission_files (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER REFERENCES submissions(id) ON DELETE CASCADE,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100),
    is_verified BOOLEAN DEFAULT FALSE,
    verification_notes TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Verification results table
CREATE TABLE verification_results (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER REFERENCES submissions(id) ON DELETE CASCADE,
    admin_id INTEGER REFERENCES users(id),
    document_name VARCHAR(255),
    status VARCHAR(10) CHECK (status IN ('MS', 'TMS')),
    notes TEXT,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Scores table
CREATE TABLE scores (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER REFERENCES submissions(id) ON DELETE CASCADE,
    assessor_id INTEGER REFERENCES users(id),
    criteria_1 INTEGER CHECK (criteria_1 >= 0 AND criteria_1 <= 100),
    criteria_2 INTEGER CHECK (criteria_2 >= 0 AND criteria_2 <= 100),
    criteria_3 INTEGER CHECK (criteria_3 >= 0 AND criteria_3 <= 100),
    criteria_4 INTEGER CHECK (criteria_4 >= 0 AND criteria_4 <= 100),
    criteria_5 INTEGER CHECK (criteria_5 >= 0 AND criteria_5 <= 100),
    total_score DECIMAL(5,2) GENERATED ALWAYS AS (
        (COALESCE(criteria_1, 0) + 
         COALESCE(criteria_2, 0) + 
         COALESCE(criteria_3, 0) + 
         COALESCE(criteria_4, 0) + 
         COALESCE(criteria_5, 0)) / 5.0
    ) STORED,
    notes TEXT,
    scored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements table
CREATE TABLE announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_type VARCHAR(50) CHECK (announcement_type IN ('general', 'selection', 'technical')),
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Audit logs table
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INTEGER,
    old_values JSONB,
    new_values JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Email queue table
CREATE TABLE email_queue (
    id SERIAL PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    template_name VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed')),
    sent_at TIMESTAMP,
    retry_count INTEGER DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Email verification tokens
CREATE TABLE email_verification_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password reset tokens
CREATE TABLE password_reset_tokens (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Session table for remember me functionality
CREATE TABLE user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address INET,
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_uuid ON users(uuid);
CREATE INDEX idx_users_is_active ON users(is_active);
CREATE INDEX idx_submissions_user_id ON submissions(user_id);
CREATE INDEX idx_submissions_vacancy_id ON submissions(vacancy_id);
CREATE INDEX idx_submissions_status ON submissions(status);
CREATE INDEX idx_submission_files_submission_id ON submission_files(submission_id);
CREATE INDEX idx_verification_results_submission_id ON verification_results(submission_id);
CREATE INDEX idx_scores_submission_id ON scores(submission_id);
CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);
CREATE INDEX idx_email_queue_status ON email_queue(status);
CREATE INDEX idx_email_queue_created_at ON email_queue(created_at);
CREATE INDEX idx_email_verification_tokens_token ON email_verification_tokens(token);
CREATE INDEX idx_email_verification_tokens_expires ON email_verification_tokens(expires_at);
CREATE INDEX idx_password_reset_tokens_token ON password_reset_tokens(token);
CREATE INDEX idx_password_reset_tokens_expires ON password_reset_tokens(expires_at);

-- User education history (multiple entries per user)
CREATE TABLE user_education (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    level VARCHAR(50) NOT NULL,
    major VARCHAR(100),
    degree VARCHAR(50),
    institution VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_user_education_user ON user_education(user_id);

-- User training/pelatihan (multiple entries per user)
CREATE TABLE user_training (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    training_name VARCHAR(255) NOT NULL,
    organizer VARCHAR(100),
    training_year INTEGER,
    certificate VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_user_training_user ON user_training(user_id);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply triggers to all tables
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_profiles_updated_at BEFORE UPDATE ON profiles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_vacancies_updated_at BEFORE UPDATE ON vacancies
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_submissions_updated_at BEFORE UPDATE ON submissions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_announcements_updated_at BEFORE UPDATE ON announcements
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to check if user can submit to vacancy
CREATE OR REPLACE FUNCTION check_vacancy_limit()
RETURNS TRIGGER AS $$
BEGIN
    -- Check if vacancy is still open
    IF (SELECT close_date FROM vacancies WHERE id = NEW.vacancy_id) < CURRENT_DATE THEN
        RAISE EXCEPTION 'Vacancy is closed';
    END IF;
    
    -- Check if vacancy is active
    IF NOT (SELECT is_active FROM vacancies WHERE id = NEW.vacancy_id) THEN
        RAISE EXCEPTION 'Vacancy is not active';
    END IF;
    
    -- Check max applicants limit
    IF (SELECT max_applicants FROM vacancies WHERE id = NEW.vacancy_id) IS NOT NULL THEN
        IF (SELECT current_applicants FROM vacancies WHERE id = NEW.vacancy_id) >= 
           (SELECT max_applicants FROM vacancies WHERE id = NEW.vacancy_id) THEN
            RAISE EXCEPTION 'Vacancy has reached maximum applicants';
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER check_vacancy_before_insert BEFORE INSERT ON submissions
    FOR EACH ROW EXECUTE FUNCTION check_vacancy_limit();

-- Function to update current_applicants count
CREATE OR REPLACE FUNCTION update_applicants_count()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE vacancies 
        SET current_applicants = current_applicants + 1 
        WHERE id = NEW.vacancy_id;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE vacancies 
        SET current_applicants = current_applicants - 1 
        WHERE id = OLD.vacancy_id;
    END IF;
    RETURN NULL;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_applicants_count AFTER INSERT OR DELETE ON submissions
    FOR EACH ROW EXECUTE FUNCTION update_applicants_count();

-- Function to log audit trail
CREATE OR REPLACE FUNCTION log_audit_trail()
RETURNS TRIGGER AS $$
DECLARE
    v_user_id INTEGER;
BEGIN
    -- Get user_id from session or set to NULL
    v_user_id := NULL;
    
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address, user_agent)
        VALUES (v_user_id, 'INSERT', TG_TABLE_NAME, NEW.id, row_to_json(NEW), 
                inet_client_addr(), current_setting('application_name'));
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (v_user_id, 'UPDATE', TG_TABLE_NAME, NEW.id, row_to_json(OLD), row_to_json(NEW),
                inet_client_addr(), current_setting('application_name'));
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, ip_address, user_agent)
        VALUES (v_user_id, 'DELETE', TG_TABLE_NAME, OLD.id, row_to_json(OLD),
                inet_client_addr(), current_setting('application_name'));
    END IF;
    
    RETURN NULL;
END;
$$ language 'plpgsql';

-- Apply audit trail to important tables
CREATE TRIGGER audit_users AFTER INSERT OR UPDATE OR DELETE ON users
    FOR EACH ROW EXECUTE FUNCTION log_audit_trail();

CREATE TRIGGER audit_submissions AFTER INSERT OR UPDATE OR DELETE ON submissions
    FOR EACH ROW EXECUTE FUNCTION log_audit_trail();

CREATE TRIGGER audit_scores AFTER INSERT OR UPDATE OR DELETE ON scores
    FOR EACH ROW EXECUTE FUNCTION log_audit_trail();