-- Run this in phpMyAdmin or MySQL

CREATE DATABASE IF NOT EXISTS qms_db;
USE qms_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes Table
CREATE TABLE IF NOT EXISTS quizzes (
    quiz_id INT PRIMARY KEY AUTO_INCREMENT,
    creator_id INT,
    title VARCHAR(200) NOT NULL,
    duration_minutes INT DEFAULT 30,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(user_id)
);

-- Questions Table
CREATE TABLE IF NOT EXISTS questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255),
    option_b VARCHAR(255),
    option_c VARCHAR(255),
    option_d VARCHAR(255),
    correct_answer CHAR(1),
    points INT DEFAULT 1,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
);

-- Submissions Table
CREATE TABLE IF NOT EXISTS submissions (
    sub_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    quiz_id INT,
    calculated_score FLOAT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
);

-- =====================
-- SAMPLE DATA
-- =====================

-- Admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Ma\'am Zobia', 'admin@qms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Student users (password: student123)
INSERT INTO users (name, email, password, role) VALUES
('Sami Ul Haq', 'sami@qms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Sareer Khan', 'sareer@qms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Shahbir Ahmad', 'shahbir@qms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Sample Quiz
INSERT INTO quizzes (creator_id, title, duration_minutes, is_active) VALUES
(1, 'COAL Mid-Term Quiz', 30, 1);

-- Sample Questions
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(1, 'What does COAL stand for?', 'Computer Oriented Assembly Language', 'Code Oriented Assembly Logic', 'Central Operating Assembly Language', 'None of these', 'A'),
(1, 'Which register holds the return address in x86?', 'EAX', 'EBX', 'ESP', 'EIP', 'D'),
(1, 'MOV instruction is used for?', 'Addition', 'Data Transfer', 'Comparison', 'Jumping', 'B'),
(1, 'What is the stack data structure?', 'FIFO', 'LIFO', 'Random Access', 'None', 'B'),
(1, 'INT 21H is used for?', 'Math operations', 'DOS interrupts/services', 'Memory allocation', 'Loop control', 'B');
