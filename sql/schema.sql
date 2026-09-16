-- ============================================================================
-- Online Law Firm Management System (OLFMS)
-- Database: olfms
-- Engine: InnoDB (transactions + foreign keys), Charset: utf8mb4
-- ============================================================================

CREATE DATABASE IF NOT EXISTS schema
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE schema;

-- ----------------------------------------------------------------------------
-- 1. roles  — supports Role Based Access Control (RBAC)
-- ----------------------------------------------------------------------------
CREATE TABLE roles (
    role_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_name   VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (role_name) VALUES
    ('admin'),
    ('manager'),
    ('lawyer'),
    ('client');

-- ----------------------------------------------------------------------------
-- 2. users — single account table for all roles (admin, manager, lawyer, client)
--    - password_hash stores an adaptive (bcrypt / password_hash()) hash
--    - failed_attempts + locked_until implement the 3-attempt lockout rule
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    user_id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name           VARCHAR(120)  NOT NULL,
    email               VARCHAR(150)  NOT NULL UNIQUE,   -- used as the login username
    password_hash       VARCHAR(255)  NOT NULL,
    role_id             INT UNSIGNED  NOT NULL,
    gender              ENUM('male','female','other') DEFAULT NULL,
    location             VARCHAR(150)  DEFAULT NULL,
    phone               VARCHAR(30)   DEFAULT NULL,
    education_level     VARCHAR(100)  DEFAULT NULL,       -- lawyers only
    is_active           TINYINT(1)    NOT NULL DEFAULT 1,
    failed_attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until        DATETIME      DEFAULT NULL,
    created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role_id);

-- ----------------------------------------------------------------------------
-- 3. password_resets — single-use, short-lived (30 second) reset tokens
--    only the SHA-256 hash of the token is stored, never the raw token
-- ----------------------------------------------------------------------------
CREATE TABLE password_resets (
    reset_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    token_hash   CHAR(64) NOT NULL UNIQUE,
    expires_at   DATETIME NOT NULL,
    used         TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- 4. cases — client case orders
-- ----------------------------------------------------------------------------
CREATE TABLE cases (
    case_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id    INT UNSIGNED NOT NULL,
    lawyer_id    INT UNSIGNED DEFAULT NULL,
    title        VARCHAR(150) NOT NULL,
    description  TEXT NOT NULL,
    category     VARCHAR(80)  NOT NULL,
    status       ENUM('pending','assigned','in_progress','closed') NOT NULL DEFAULT 'pending',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_case_client FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_case_lawyer FOREIGN KEY (lawyer_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_cases_client ON cases(client_id);
CREATE INDEX idx_cases_lawyer ON cases(lawyer_id);
CREATE INDEX idx_cases_status ON cases(status);

-- ----------------------------------------------------------------------------
-- 5. appointments — client requested / lawyer reviewed appointments
-- ----------------------------------------------------------------------------
CREATE TABLE appointments (
    appointment_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id          INT UNSIGNED DEFAULT NULL,
    client_id        INT UNSIGNED NOT NULL,
    lawyer_id        INT UNSIGNED DEFAULT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    purpose          VARCHAR(255) NOT NULL,
    status           ENUM('pending','accepted','declined','completed') NOT NULL DEFAULT 'pending',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appt_case   FOREIGN KEY (case_id)   REFERENCES cases(case_id)   ON DELETE SET NULL,
    CONSTRAINT fk_appt_client FOREIGN KEY (client_id) REFERENCES users(user_id)   ON DELETE CASCADE,
    CONSTRAINT fk_appt_lawyer FOREIGN KEY (lawyer_id) REFERENCES users(user_id)   ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_appt_client ON appointments(client_id);
CREATE INDEX idx_appt_lawyer ON appointments(lawyer_id);

-- ----------------------------------------------------------------------------
-- 6. files — uploaded PDF / PNG documents (case documents & law/constitution docs)
--    stored_name is a random server-generated key; original_name kept for display
-- ----------------------------------------------------------------------------
CREATE TABLE files (
    file_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id        INT UNSIGNED DEFAULT NULL,
    uploaded_by    INT UNSIGNED NOT NULL,
    category       ENUM('case_document','law_document') NOT NULL,
    original_name  VARCHAR(255) NOT NULL,
    stored_name    VARCHAR(255) NOT NULL UNIQUE,
    mime_type      VARCHAR(100) NOT NULL,
    file_size      INT UNSIGNED NOT NULL,
    uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_file_case FOREIGN KEY (case_id)     REFERENCES cases(case_id) ON DELETE CASCADE,
    CONSTRAINT fk_file_user FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_files_case ON files(case_id);

-- ----------------------------------------------------------------------------
-- 7. audit_log — records login attempts and sensitive access (privacy / TC-08)
-- ----------------------------------------------------------------------------
CREATE TABLE audit_log (
    log_id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED DEFAULT NULL,
    action       VARCHAR(100) NOT NULL,
    description  VARCHAR(255) DEFAULT NULL,
    ip_address   VARCHAR(45)  DEFAULT NULL,
    status       ENUM('success','failure') NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_audit_created ON audit_log(created_at);

-- ----------------------------------------------------------------------------
-- NOTE on the initial administrator account:
-- Password hashes must be produced by PHP's password_hash() (bcrypt, adaptive,
-- salted per-call) so they cannot be safely hard-coded as portable SQL text.
-- Run config/install.php ONCE after importing this schema — it creates the
-- first "admin" account interactively (or with the defaults shown on the
-- page) using password_hash(), then locks itself so it cannot run again.
-- ----------------------------------------------------------------------------
