SET NAMES utf8mb4;
SET time_zone = '+00:00';
 
CREATE DATABASE IF NOT EXISTS erpsc_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
 
USE erpsc_db;
 
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  e_number VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
CREATE TABLE IF NOT EXISTS attendance_events (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  label VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
CREATE TABLE IF NOT EXISTS attendance_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  event_id TINYINT UNSIGNED NOT NULL,
  checked_at DATETIME NOT NULL,
  client_uuid VARCHAR(64) NOT NULL UNIQUE,
  source ENUM('qr', 'manual', 'sync') NOT NULL DEFAULT 'qr',
  status ENUM('Asistió', 'Retardo', 'No asistió') NOT NULL DEFAULT 'Asistió',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_checked_at (checked_at),
  INDEX idx_logs_user_date (user_id, checked_at),
  INDEX idx_logs_status (status),
  CONSTRAINT fk_logs_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_logs_event
    FOREIGN KEY (event_id) REFERENCES attendance_events (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
INSERT IGNORE INTO attendance_events (code, label) VALUES ('entrada', 'Entrada');
INSERT IGNORE INTO attendance_events (code, label) VALUES ('salida', 'Salida');
INSERT IGNORE INTO users (e_number, full_name, is_active) VALUES ('1001', 'Usuario Prueba Uno', 1);
INSERT IGNORE INTO users (e_number, full_name, is_active) VALUES ('1002', 'Usuario Prueba Dos', 1);
