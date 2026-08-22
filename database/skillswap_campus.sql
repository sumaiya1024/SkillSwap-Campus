-- =============================================
-- SkillSwap Campus — Database Schema
-- University DBMS Mini-Project
-- =============================================

CREATE DATABASE IF NOT EXISTS skillswap_campus;
USE skillswap_campus;

-- =============================================
-- Table: users
-- Purpose: Authentication (login credentials, role)
-- =============================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Table: students
-- Purpose: Student profile details (extends users)
-- =============================================
CREATE TABLE students (
    student_id INT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    university_id VARCHAR(20) UNIQUE,
    department VARCHAR(100),
    bio TEXT,
    profile_picture VARCHAR(255),
    phone VARCHAR(20),
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: skill_categories
-- Purpose: Grouping of skills
-- =============================================
CREATE TABLE skill_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- =============================================
-- Table: skills
-- Purpose: Individual skills within a category
-- =============================================
CREATE TABLE skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES skill_categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: student_skills
-- Purpose: Junction — which student teaches which skill
-- =============================================
CREATE TABLE student_skills (
    student_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL DEFAULT 'Beginner',
    UNIQUE KEY unique_student_skill (student_id, skill_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: skill_requests
-- Purpose: Learner requests to learn a skill from a provider
-- =============================================
CREATE TABLE skill_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT NOT NULL,
    provider_id INT NOT NULL,
    skill_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    message TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: sessions
-- Purpose: Scheduled meeting after request is accepted
-- =============================================
CREATE TABLE sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 60,
    location VARCHAR(255),
    status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES skill_requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Table: reviews
-- Purpose: Rating/review after a completed session
-- =============================================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session_reviewer (session_id, reviewer_id),
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- =============================================
-- Sample Data
-- =============================================

-- Admin user (password: admin123)
INSERT INTO users (email, password, role) VALUES
('admin@skillswap.com', '$2y$10$8K1p/a0dL1LXMw0HQ7p6Pu5HkPqPq0vqyQW0Z7YB3mF8z3vGpKxSy', 'admin');

-- Sample students (password: password123)
INSERT INTO users (email, password, role) VALUES
('alice@university.edu', '$2y$10$8K1p/a0dL1LXMw0HQ7p6Pu5HkPqPq0vqyQW0Z7YB3mF8z3vGpKxSy', 'student'),
('bob@university.edu', '$2y$10$8K1p/a0dL1LXMw0HQ7p6Pu5HkPqPq0vqyQW0Z7YB3mF8z3vGpKxSy', 'student'),
('carol@university.edu', '$2y$10$8K1p/a0dL1LXMw0HQ7p6Pu5HkPqPq0vqyQW0Z7YB3mF8z3vGpKxSy', 'student');

INSERT INTO students (student_id, full_name, university_id, department, bio, phone) VALUES
(2, 'Alice Johnson', 'STU-2024-001', 'Computer Science', 'I love coding and web development!', '01712345678'),
(3, 'Bob Smith', 'STU-2024-002', 'Electrical Engineering', 'Guitar player and circuit designer.', '01798765432'),
(4, 'Carol Williams', 'STU-2024-003', 'Business Administration', 'Passionate about design and marketing.', '01756781234');

-- Skill categories
INSERT INTO skill_categories (category_name) VALUES
('Programming'),
('Music'),
('Design'),
('Languages'),
('Academic'),
('Sports & Fitness');

-- Skills
INSERT INTO skills (skill_name, category_id) VALUES
('Python', 1),
('JavaScript', 1),
('Java', 1),
('C++', 1),
('Web Development', 1),
('Guitar', 2),
('Piano', 2),
('Singing', 2),
('Graphic Design', 3),
('UI/UX Design', 3),
('Video Editing', 3),
('English', 4),
('Spanish', 4),
('Japanese', 4),
('Mathematics', 5),
('Physics', 5),
('Essay Writing', 5),
('Football', 6),
('Basketball', 6),
('Yoga', 6);

-- Student skills
INSERT INTO student_skills (student_id, skill_id, proficiency_level) VALUES
(2, 1, 'Advanced'),
(2, 2, 'Intermediate'),
(2, 5, 'Advanced'),
(3, 6, 'Advanced'),
(3, 4, 'Intermediate'),
(3, 16, 'Advanced'),
(4, 9, 'Advanced'),
(4, 10, 'Intermediate'),
(4, 12, 'Advanced');

-- Sample skill request
INSERT INTO skill_requests (requester_id, provider_id, skill_id, status, message) VALUES
(3, 2, 1, 'accepted', 'Hey Alice! I want to learn Python for my embedded systems project.'),
(4, 3, 6, 'pending', 'Hi Bob, I would love to learn guitar!');

-- Sample session
INSERT INTO sessions (request_id, session_date, session_time, duration_minutes, location, status) VALUES
(1, '2026-08-25', '14:00:00', 60, 'Library Room 204', 'completed');

-- Sample review
INSERT INTO reviews (session_id, reviewer_id, reviewee_id, rating, comment) VALUES
(1, 3, 2, 5, 'Alice is an amazing Python teacher! Very patient and explains concepts clearly.');
