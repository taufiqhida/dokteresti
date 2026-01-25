-- Database schema untuk Skrining Kesehatan Mental
-- Jalankan script ini di phpMyAdmin atau MySQL CLI

-- Buat database
CREATE DATABASE IF NOT EXISTS `esti` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `esti`;

-- Tabel hasil skrining
CREATE TABLE IF NOT EXISTS `screening_results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `screening_type` ENUM('EPDS', 'PHQ2-GAD2') NOT NULL COMMENT 'Jenis instrumen skrining',
    `respondent_name` VARCHAR(100) NOT NULL COMMENT 'Nama responden',
    `respondent_phone` VARCHAR(20) DEFAULT NULL COMMENT 'Nomor HP responden',
    `respondent_age` INT DEFAULT NULL COMMENT 'Usia responden',
    `answers` JSON NOT NULL COMMENT 'Jawaban dalam format JSON',
    `total_score` INT NOT NULL COMMENT 'Total skor',
    `phq2_score` INT DEFAULT NULL COMMENT 'Skor PHQ-2 (untuk form PHQ2-GAD2)',
    `gad2_score` INT DEFAULT NULL COMMENT 'Skor GAD-2 (untuk form PHQ2-GAD2)',
    `interpretation` VARCHAR(50) NOT NULL COMMENT 'Hasil interpretasi (RENDAH/SEDANG/TINGGI)',
    `is_critical` TINYINT(1) DEFAULT 0 COMMENT 'Flag jika ada jawaban kritis',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_screening_type` (`screening_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_interpretation` (`interpretation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
