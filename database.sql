-- =============================================
-- SkillSwap Campus — Database Schema
-- University DBMS Mini-Project
-- MySQL 8 | Works in XAMPP / phpMyAdmin
-- =============================================

-- Create and use database
DROP DATABASE IF EXISTS skillswap_campus;
CREATE DATABASE skillswap_campus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skillswap_campus;


-- =============================================
-- 1. users
-- Purpose: Authentication (email, hashed password, role)
-- =============================================
CREATE TABLE users (
    user_id         INT             AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(100)    NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL,
    role            ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_users_role (role)
) ENGINE=InnoDB;


-- =============================================
-- 2. students
-- Purpose: Student profile (extends users via 1:1 FK)
-- =============================================
CREATE TABLE students (
    student_id      INT             PRIMARY KEY,
    full_name       VARCHAR(100)    NOT NULL,
    university_id   VARCHAR(20)     NULL UNIQUE,
    department      VARCHAR(100)    NULL,
    bio             TEXT            NULL,
    profile_picture VARCHAR(255)    NULL,
    phone           VARCHAR(20)    NULL,

    CONSTRAINT fk_students_user
        FOREIGN KEY (student_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- 3. skill_categories
-- Purpose: Grouping of skills (e.g. Programming, Music)
-- =============================================
CREATE TABLE skill_categories (
    category_id     INT             AUTO_INCREMENT PRIMARY KEY,
    category_name   VARCHAR(100)    NOT NULL UNIQUE
) ENGINE=InnoDB;


-- =============================================
-- 4. skills
-- Purpose: Individual skills belonging to a category
-- =============================================
CREATE TABLE skills (
    skill_id        INT             AUTO_INCREMENT PRIMARY KEY,
    skill_name      VARCHAR(100)    NOT NULL,
    category_id     INT             NOT NULL,

    INDEX idx_skills_category (category_id),

    CONSTRAINT fk_skills_category
        FOREIGN KEY (category_id) REFERENCES skill_categories(category_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- 5. student_skills
-- Purpose: Junction table — which student teaches which skill
--          Resolves the M:N relationship between students & skills
-- =============================================
CREATE TABLE student_skills (
    student_skill_id    INT         AUTO_INCREMENT PRIMARY KEY,
    student_id          INT         NOT NULL,
    skill_id            INT         NOT NULL,
    proficiency_level   ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL DEFAULT 'Beginner',

    UNIQUE KEY uq_student_skill (student_id, skill_id),

    INDEX idx_ss_skill (skill_id),

    CONSTRAINT fk_ss_student
        FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_ss_skill
        FOREIGN KEY (skill_id) REFERENCES skills(skill_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- 6. skill_requests
-- Purpose: A learner (requester) asks a provider to teach a skill
-- =============================================
CREATE TABLE skill_requests (
    request_id      INT             AUTO_INCREMENT PRIMARY KEY,
    requester_id    INT             NOT NULL,
    provider_id     INT             NOT NULL,
    skill_id        INT             NOT NULL,
    status          ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    message         TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_req_requester (requester_id),
    INDEX idx_req_provider  (provider_id),
    INDEX idx_req_status    (status),

    CONSTRAINT fk_req_requester
        FOREIGN KEY (requester_id) REFERENCES students(student_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_req_provider
        FOREIGN KEY (provider_id) REFERENCES students(student_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_req_skill
        FOREIGN KEY (skill_id) REFERENCES skills(skill_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- 7. sessions
-- Purpose: Scheduled meeting after a request is accepted
-- =============================================
CREATE TABLE sessions (
    session_id      INT             AUTO_INCREMENT PRIMARY KEY,
    request_id      INT             NOT NULL,
    session_date    DATE            NOT NULL,
    session_time    TIME            NOT NULL,
    duration_minutes INT            NOT NULL DEFAULT 60,
    location        VARCHAR(255)    NULL,
    status          ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ses_request (request_id),
    INDEX idx_ses_status  (status),

    CONSTRAINT fk_ses_request
        FOREIGN KEY (request_id) REFERENCES skill_requests(request_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- 8. reviews
-- Purpose: Rating & feedback after a completed session
--          Each participant can leave one review per session
-- =============================================
CREATE TABLE reviews (
    review_id       INT             AUTO_INCREMENT PRIMARY KEY,
    session_id      INT             NOT NULL,
    reviewer_id     INT             NOT NULL,
    reviewee_id     INT             NOT NULL,
    rating          TINYINT         NOT NULL,
    comment         TEXT            NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_session_reviewer (session_id, reviewer_id),

    INDEX idx_rev_reviewee (reviewee_id),

    CONSTRAINT chk_rating CHECK (rating >= 1 AND rating <= 5),

    CONSTRAINT fk_rev_session
        FOREIGN KEY (session_id) REFERENCES sessions(session_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_rev_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES students(student_id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_rev_reviewee
        FOREIGN KEY (reviewee_id) REFERENCES students(student_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- =============================================================================
-- SAMPLE DATA
-- =============================================================================

-- ----- Users -----
-- Admin password: admin123  |  Student password: password123
-- Hashes generated with PHP password_hash('...', PASSWORD_DEFAULT)
INSERT INTO users (user_id, email, password, role) VALUES
(1, 'admin@skillswap.com',    '$2y$10$t63GDfnOPbwqOSrXYZi0mOd7RrHTLfJ.qidEouC6cbmGfFwz6yqgi', 'admin'),
(2, 'alice@university.edu',   '$2y$10$ARrynK4DXxfhMWaA2v6zzup3rL07ns4ReAQcf1Yin9n1ryEMyr2/G', 'student'),
(3, 'bob@university.edu',     '$2y$10$ARrynK4DXxfhMWaA2v6zzup3rL07ns4ReAQcf1Yin9n1ryEMyr2/G', 'student'),
(4, 'carol@university.edu',   '$2y$10$ARrynK4DXxfhMWaA2v6zzup3rL07ns4ReAQcf1Yin9n1ryEMyr2/G', 'student'),
(5, 'david@university.edu',   '$2y$10$ARrynK4DXxfhMWaA2v6zzup3rL07ns4ReAQcf1Yin9n1ryEMyr2/G', 'student');


-- ----- Students -----
INSERT INTO students (student_id, full_name, university_id, department, bio, phone) VALUES
(2, 'Alice Johnson',   'STU-2024-001', 'Computer Science',         'I love coding and web development! Happy to teach Python, JS, and web dev basics.',   '01712345678'),
(3, 'Bob Smith',        'STU-2024-002', 'Electrical Engineering',   'Guitar player and circuit designer. Let\'s jam or learn together!',                   '01798765432'),
(4, 'Carol Williams',   'STU-2024-003', 'Business Administration',  'Passionate about graphic design and digital marketing.',                              '01756781234'),
(5, 'David Lee',        'STU-2024-004', 'English Literature',       'Language enthusiast. Fluent in English, Spanish, and learning Japanese.',              '01723456789');


-- ----- Skill Categories -----
INSERT INTO skill_categories (category_id, category_name) VALUES
(1, 'Programming'),
(2, 'Music'),
(3, 'Design'),
(4, 'Languages'),
(5, 'Academic'),
(6, 'Sports & Fitness');


-- ----- Skills -----
INSERT INTO skills (skill_id, skill_name, category_id) VALUES
-- Programming
( 1, 'Python',           1),
( 2, 'JavaScript',       1),
( 3, 'Java',             1),
( 4, 'C++',              1),
( 5, 'Web Development',  1),
-- Music
( 6, 'Guitar',           2),
( 7, 'Piano',            2),
( 8, 'Singing',          2),
-- Design
( 9, 'Graphic Design',   3),
(10, 'UI/UX Design',     3),
(11, 'Video Editing',    3),
-- Languages
(12, 'English',          4),
(13, 'Spanish',          4),
(14, 'Japanese',         4),
-- Academic
(15, 'Mathematics',      5),
(16, 'Physics',          5),
(17, 'Essay Writing',    5),
-- Sports & Fitness
(18, 'Football',         6),
(19, 'Basketball',       6),
(20, 'Yoga',             6);


-- ----- Student Skills (who teaches what) -----
INSERT INTO student_skills (student_id, skill_id, proficiency_level) VALUES
-- Alice teaches programming & web
(2,  1, 'Advanced'),       -- Python
(2,  2, 'Intermediate'),   -- JavaScript
(2,  5, 'Advanced'),       -- Web Development
-- Bob teaches guitar & physics
(3,  6, 'Advanced'),       -- Guitar
(3,  4, 'Intermediate'),   -- C++
(3, 16, 'Advanced'),       -- Physics
-- Carol teaches design & English
(4,  9, 'Advanced'),       -- Graphic Design
(4, 10, 'Intermediate'),   -- UI/UX Design
(4, 12, 'Advanced'),       -- English
-- David teaches languages & essay writing
(5, 12, 'Advanced'),       -- English
(5, 13, 'Advanced'),       -- Spanish
(5, 14, 'Intermediate'),   -- Japanese
(5, 17, 'Advanced');       -- Essay Writing


-- ----- Skill Requests -----
INSERT INTO skill_requests (request_id, requester_id, provider_id, skill_id, status, message) VALUES
(1, 3, 2, 1, 'accepted',  'Hey Alice! I want to learn Python for my embedded systems project. Can you help?'),
(2, 4, 3, 6, 'pending',   'Hi Bob, I would love to learn guitar! Are you free this week?'),
(3, 2, 4, 9, 'accepted',  'Carol, I need help with poster design for our CS club event.'),
(4, 5, 2, 2, 'pending',   'Alice, I want to learn JavaScript to build a personal website.'),
(5, 3, 5, 13, 'rejected', 'David, can you teach me basic Spanish conversation?');


-- ----- Sessions -----
INSERT INTO sessions (session_id, request_id, session_date, session_time, duration_minutes, location, status) VALUES
(1, 1, '2026-08-25', '14:00:00', 60,  'Library Room 204',    'completed'),
(2, 1, '2026-08-28', '15:00:00', 90,  'Library Room 204',    'scheduled'),
(3, 3, '2026-08-26', '10:00:00', 60,  'Design Lab 101',      'completed');


-- ----- Reviews -----
INSERT INTO reviews (review_id, session_id, reviewer_id, reviewee_id, rating, comment) VALUES
(1, 1, 3, 2, 5, 'Alice is an amazing Python teacher! Very patient and explains concepts clearly.'),
(2, 1, 2, 3, 4, 'Bob was a great learner, very attentive and asked good questions.'),
(3, 3, 2, 4, 5, 'Carol made graphic design feel so easy. Loved the session!'),
(4, 3, 4, 2, 5, 'Alice was super eager to learn. Great exchange!');
