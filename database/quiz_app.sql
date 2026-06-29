-- Quiz App Database Schema
-- Version: 1.0
-- Created: 2026-06-23

CREATE DATABASE IF NOT EXISTS `quiz_app` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `quiz_app`;

-- ============================================================================
-- ADMINS TABLE
-- ============================================================================
CREATE TABLE `admins` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150),
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `role` ENUM('super_admin', 'quiz_master', 'result_manager', 'viewer') DEFAULT 'viewer',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `last_login` DATETIME,
  `remember_token` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TEAMS TABLE
-- ============================================================================
CREATE TABLE `teams` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `school_name` VARCHAR(255) NOT NULL,
  `team_name` VARCHAR(255) NOT NULL,
  `leader_name` VARCHAR(150) NOT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150),
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_status` (`status`),
  INDEX `idx_school_name` (`school_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ROUNDS TABLE
-- ============================================================================
CREATE TABLE `rounds` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `sequence` INT NOT NULL,
  `status` ENUM('active', 'inactive', 'locked', 'completed') DEFAULT 'inactive',
  `time_per_question` INT DEFAULT 30 COMMENT 'Time in seconds',
  `total_questions` INT DEFAULT 0,
  `total_marks` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sequence` (`sequence`),
  INDEX `idx_status` (`status`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- QUESTIONS TABLE
-- ============================================================================
CREATE TABLE `questions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `round_id` INT NOT NULL,
  `question_text` LONGTEXT,
  `question_image` VARCHAR(255),
  `question_type` ENUM('text', 'image', 'mixed') DEFAULT 'text',
  `marks` INT DEFAULT 1,
  `time_limit` INT DEFAULT 30 COMMENT 'Time in seconds',
  `sequence` INT NOT NULL,
  `correct_answer` CHAR(1) NOT NULL COMMENT 'A, B, C, or D',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`round_id`) REFERENCES `rounds`(`id`) ON DELETE CASCADE,
  INDEX `idx_round_id` (`round_id`),
  INDEX `idx_sequence` (`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- QUESTION OPTIONS TABLE
-- ============================================================================
CREATE TABLE `question_options` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `question_id` INT NOT NULL,
  `option_letter` CHAR(1) NOT NULL COMMENT 'A, B, C, or D',
  `option_text` LONGTEXT,
  `option_image` VARCHAR(255),
  `option_type` ENUM('text', 'image') DEFAULT 'text',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
  INDEX `idx_question_id` (`question_id`),
  INDEX `idx_option_letter` (`option_letter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TEAM ANSWERS TABLE
-- ============================================================================
CREATE TABLE `team_answers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `round_id` INT NOT NULL,
  `selected_answer` CHAR(1),
  `is_correct` TINYINT(1) DEFAULT 0,
  `marks_obtained` INT DEFAULT 0,
  `answered_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`round_id`) REFERENCES `rounds`(`id`) ON DELETE CASCADE,
  INDEX `idx_team_id` (`team_id`),
  INDEX `idx_question_id` (`question_id`),
  INDEX `idx_round_id` (`round_id`),
  UNIQUE KEY `unique_answer` (`team_id`, `question_id`, `round_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ROUND RESULTS TABLE
-- ============================================================================
CREATE TABLE `round_results` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `round_id` INT NOT NULL,
  `total_marks` INT DEFAULT 0,
  `total_questions` INT DEFAULT 0,
  `correct_answers` INT DEFAULT 0,
  `wrong_answers` INT DEFAULT 0,
  `skipped_answers` INT DEFAULT 0,
  `percentage` DECIMAL(5,2) DEFAULT 0,
  `status` ENUM('completed', 'pending', 'submitted') DEFAULT 'pending',
  `started_at` DATETIME,
  `completed_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`round_id`) REFERENCES `rounds`(`id`) ON DELETE CASCADE,
  INDEX `idx_team_id` (`team_id`),
  INDEX `idx_round_id` (`round_id`),
  INDEX `idx_status` (`status`),
  UNIQUE KEY `unique_result` (`team_id`, `round_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- OVERALL RESULTS TABLE (Cumulative)
-- ============================================================================
CREATE TABLE `overall_results` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `team_id` INT NOT NULL UNIQUE,
  `total_marks` INT DEFAULT 0,
  `total_correct` INT DEFAULT 0,
  `total_wrong` INT DEFAULT 0,
  `total_skipped` INT DEFAULT 0,
  `rounds_completed` INT DEFAULT 0,
  `percentage` DECIMAL(5,2) DEFAULT 0,
  `rank` INT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  INDEX `idx_total_marks` (`total_marks`),
  INDEX `idx_rank` (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SETTINGS TABLE
-- ============================================================================
CREATE TABLE `settings` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` LONGTEXT,
  `description` VARCHAR(255),
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ACTIVITY LOGS TABLE
-- ============================================================================
CREATE TABLE `activity_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `admin_id` INT,
  `team_id` INT,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(100),
  `entity_id` INT,
  `entity_type` VARCHAR(50),
  `old_values` JSON,
  `new_values` JSON,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
  INDEX `idx_admin_id` (`admin_id`),
  INDEX `idx_team_id` (`team_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_module` (`module`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- BACKUPS TABLE
-- ============================================================================
CREATE TABLE `backups` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `backup_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` BIGINT,
  `backup_type` ENUM('full', 'partial') DEFAULT 'full',
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT,
  FOREIGN KEY (`created_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERT DEFAULT SETTINGS
-- ============================================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('quiz_title', 'Inter School Quiz Competition', 'Title of the quiz'),
('organization_name', 'Quiz Management System', 'Organization name'),
('logo', '', 'Logo file path'),
('theme_color', '#007bff', 'Primary theme color'),
('footer_text', '© 2026 Quiz Management System. All rights reserved.', 'Footer text'),
('session_timeout', '1800', 'Session timeout in seconds'),
('enable_remember_me', '1', 'Enable remember me functionality'),
('max_login_attempts', '5', 'Maximum login attempts'),
('lockout_duration', '900', 'Account lockout duration in seconds');

-- ============================================================================
-- INSERT DEFAULT ADMIN
-- ============================================================================
INSERT INTO `admins` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `status`) VALUES
('admin', 'admin@quizapp.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'Quiz', 'Administrator', 'super_admin', 'active');

-- ============================================================================
-- INSERT SAMPLE ROUNDS
-- ============================================================================
INSERT INTO `rounds` (`name`, `description`, `sequence`, `status`, `time_per_question`, `total_questions`, `total_marks`) VALUES
('Elimination Round', 'First round - Elimination round', 1, 'inactive', 30, 10, 100),
('Rapid Fire Round', 'Second round - Rapid fire questions', 2, 'inactive', 15, 20, 200),
('Audio Round', 'Third round - Audio-based questions', 3, 'inactive', 45, 8, 80),
('Visual Round', 'Fourth round - Image-based questions', 4, 'inactive', 30, 12, 120),
('Grand Finale', 'Final round - Grand finale', 5, 'inactive', 60, 5, 150);

-- ============================================================================
-- INSERT SAMPLE TEAMS
-- ============================================================================
INSERT INTO `teams` (`school_name`, `team_name`, `leader_name`, `username`, `email`, `password`, `status`) VALUES
('St. Mary School', 'Team Alpha', 'John Doe', 'team_alpha', 'alpha@stmary.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'active'),
('Lincoln High', 'Team Beta', 'Jane Smith', 'team_beta', 'beta@lincoln.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'active'),
('Central Academy', 'Team Gamma', 'Michael Brown', 'team_gamma', 'gamma@central.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'active'),
('Kings College', 'Team Delta', 'Sarah Wilson', 'team_delta', 'delta@kings.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'active'),
('Green Valley', 'Team Epsilon', 'Robert Taylor', 'team_epsilon', 'epsilon@green.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq', 'active');

-- ============================================================================
-- INSERT SAMPLE QUESTIONS FOR ROUND 1 (Elimination Round)
-- ============================================================================
INSERT INTO `questions` (`round_id`, `question_text`, `question_type`, `marks`, `time_limit`, `sequence`, `correct_answer`) VALUES
(1, 'What is the capital of France?', 'text', 10, 30, 1, 'A'),
(1, 'Which planet is known as the Red Planet?', 'text', 10, 30, 2, 'B'),
(1, 'Who wrote Romeo and Juliet?', 'text', 10, 30, 3, 'C'),
(1, 'What is the chemical symbol for Gold?', 'text', 10, 30, 4, 'D'),
(1, 'In which year did the Titanic sink?', 'text', 10, 30, 5, 'A'),
(1, 'What is the largest ocean on Earth?', 'text', 10, 30, 6, 'B'),
(1, 'Who is the author of Harry Potter?', 'text', 10, 30, 7, 'C'),
(1, 'What is 2 + 2 * 3?', 'text', 10, 30, 8, 'D'),
(1, 'Which country is home to the Great Wall?', 'text', 10, 30, 9, 'A'),
(1, 'What is the speed of light in vacuum?', 'text', 10, 30, 10, 'B');

-- ============================================================================
-- INSERT SAMPLE OPTIONS FOR ROUND 1 QUESTIONS
-- ============================================================================
INSERT INTO `question_options` (`question_id`, `option_letter`, `option_text`, `option_type`) VALUES
(1, 'A', 'Paris', 'text'), (1, 'B', 'London', 'text'), (1, 'C', 'Berlin', 'text'), (1, 'D', 'Madrid', 'text'),
(2, 'A', 'Venus', 'text'), (2, 'B', 'Mars', 'text'), (2, 'C', 'Jupiter', 'text'), (2, 'D', 'Saturn', 'text'),
(3, 'A', 'Charles Dickens', 'text'), (3, 'B', 'Jane Austen', 'text'), (3, 'C', 'William Shakespeare', 'text'), (3, 'D', 'George Orwell', 'text'),
(4, 'A', 'Au', 'text'), (4, 'B', 'Ag', 'text'), (4, 'C', 'Gd', 'text'), (4, 'D', 'Fe', 'text'),
(5, 'A', '1912', 'text'), (5, 'B', '1905', 'text'), (5, 'C', '1920', 'text'), (5, 'D', '1898', 'text'),
(6, 'A', 'Atlantic Ocean', 'text'), (6, 'B', 'Pacific Ocean', 'text'), (6, 'C', 'Indian Ocean', 'text'), (6, 'D', 'Arctic Ocean', 'text'),
(7, 'A', 'George R. R. Martin', 'text'), (7, 'B', 'Stephen King', 'text'), (7, 'C', 'J. K. Rowling', 'text'), (7, 'D', 'Brandon Sanderson', 'text'),
(8, 'A', '6', 'text'), (8, 'B', '8', 'text'), (8, 'C', '10', 'text'), (8, 'D', '12', 'text'),
(9, 'A', 'China', 'text'), (9, 'B', 'Japan', 'text'), (9, 'C', 'Korea', 'text'), (9, 'D', 'Vietnam', 'text'),
(10, 'A', '150,000 km/s', 'text'), (10, 'B', '300,000 km/s', 'text'), (10, 'C', '100,000 km/s', 'text'), (10, 'D', '250,000 km/s', 'text');

-- ============================================================================
-- CREATE INDEXES FOR PERFORMANCE
-- ============================================================================
CREATE INDEX `idx_overall_total_marks` ON `overall_results` (`total_marks`);
CREATE INDEX `idx_round_results_team` ON `round_results` (`team_id`, `round_id`);
CREATE INDEX `idx_team_answers_team` ON `team_answers` (`team_id`, `round_id`);
