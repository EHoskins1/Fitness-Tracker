-- FITNESS TRACKER DATABASE SCHEMA
-- Version: 1.0
-- Created: 2026-02-20

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- PASSWORD RESETS TABLE
-- =====================================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================================
-- SESSION TYPES TABLE
-- =====================================================
CREATE TABLE session_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50),
    is_default BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- Insert default session types
INSERT INTO session_types (user_id, name, icon, is_default) VALUES
(NULL, 'training', '🥊', TRUE),
(NULL, 'run', '🏃', TRUE),
(NULL, 'workout', '💪', TRUE),
(NULL, 'swimming', '🏊', TRUE),
(NULL, 'cycling', '🚴', TRUE),
(NULL, 'hiking', '🥾', TRUE),
(NULL, 'stretching', '🧘', TRUE);

-- =====================================================
-- BODY METRICS TABLE
-- =====================================================
CREATE TABLE body_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    weight_kg DECIMAL(5,2),
    body_fat_percent DECIMAL(5,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_body_metrics_user_date (user_id, date),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================================
-- SESSIONS TABLE
-- =====================================================
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME,
    duration_minutes INT,
    session_type VARCHAR(50) NOT NULL,
    intensity TINYINT,
    location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_sessions_user_date (user_id, date),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================================
-- SESSION DETAILS TABLE (KEY-VALUE STORE)
-- =====================================================
CREATE TABLE session_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    detail_key VARCHAR(100),
    detail_value VARCHAR(255),

    INDEX idx_session_details_session (session_id),

    FOREIGN KEY (session_id)
        REFERENCES sessions(id)
        ON DELETE CASCADE
);

-- =====================================================
-- WORKOUTS TABLE (TEMPLATES)
-- =====================================================
CREATE TABLE workouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================================
-- WORKOUT ENTRIES TABLE
-- =====================================================
CREATE TABLE workout_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workout_id INT NOT NULL,
    session_id INT NOT NULL,

    FOREIGN KEY (workout_id)
        REFERENCES workouts(id)
        ON DELETE CASCADE,

    FOREIGN KEY (session_id)
        REFERENCES sessions(id)
        ON DELETE CASCADE
);
