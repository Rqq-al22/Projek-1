-- =====================================================
-- DATABASE LABIRIN - OPTIMIZED FOR XAMPP
-- =====================================================
-- 
-- INSTRUKSI INSTALASI XAMPP:
-- 1. Pastikan XAMPP sudah running (Apache + MySQL)
-- 2. Buka phpMyAdmin: http://localhost/phpmyadmin
-- 3. Import file ini atau copy-paste ke SQL tab
-- 4. Database akan dibuat otomatis: labirin_db
-- 
-- KREDENSIAL LOGIN:
-- Terapis: T001 / password_terapis
-- Orangtua: O001 / password_ortu
-- 
-- =====================================================
CREATE DATABASE IF NOT EXISTS labirin_db 
CHARACTER SET utf8 
COLLATE utf8_general_ci;
USE labirin_db;

-- =====================================================
-- TABEL USERS (login: orangtua & terapis)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('terapis','orangtua') NOT NULL,
    nama_lengkap VARCHAR(100),
    gender ENUM('male','female') NULL,
    email VARCHAR(160) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Contoh data awal (password: password_terapis dan password_ortu)
-- Hash dibuat dengan: password_hash('password_terapis', PASSWORD_BCRYPT)
INSERT IGNORE INTO users (username, password, role, nama_lengkap, gender, email) VALUES
('T001', '$2y$10$Y7ICIkMmHclPKi1kaQaw9.ID51siTQozImd7wU411WVgGKjXPP8Cq', 'terapis', 'Budi Terapis', 'male', 'budi.terapis@example.com'),
('T002', '$2y$10$Y7ICIkMmHclPKi1kaQaw9.ID51siTQozImd7wU411WVgGKjXPP8Cq', 'terapis', 'Sinta Terapis', 'female', 'sinta.terapis@example.com'),
('O001', '$2y$10$R3/2TXnCZnImC98KuhTNAeHpixXgMhZNJCuXlevOVFQnWCN4P4oQO', 'orangtua', 'Ibu Sari', 'female', 'ibu.sari@example.com'),
('O002', '$2y$10$R3/2TXnCZnImC98KuhTNAeHpixXgMhZNJCuXlevOVFQnWCN4P4oQO', 'orangtua', 'Pak Andi', 'male', 'pak.andi@example.com');

-- =====================================================
-- TABEL ANAK (anak milik orangtua)
-- =====================================================
CREATE TABLE IF NOT EXISTS anak (
    anak_id INT AUTO_INCREMENT PRIMARY KEY,
    orangtua_id INT NOT NULL,
    nama_anak VARCHAR(100) NOT NULL,
    tanggal_lahir DATE,
    keterangan TEXT,
    FOREIGN KEY (orangtua_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Contoh data anak
INSERT IGNORE INTO anak (orangtua_id, nama_anak, tanggal_lahir, keterangan) VALUES
(3, 'Ayu', '2018-05-10', 'Perlu terapi wicara'),
(4, 'Bima', '2017-11-22', 'Terapi okupasi');

-- =====================================================
-- TABEL PAKET BELAJAR (misal 20x pertemuan per bulan)
-- =====================================================
CREATE TABLE IF NOT EXISTS paket_belajar (
    paket_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    nama_paket VARCHAR(100),
    jumlah_pertemuan INT DEFAULT 20,
    max_reschedule INT DEFAULT 4, -- izin maksimal
    bulan VARCHAR(20),
    tahun INT,
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Contoh paket belajar
INSERT IGNORE INTO paket_belajar (anak_id, nama_paket, jumlah_pertemuan, max_reschedule, bulan, tahun) VALUES
(1, 'Paket Wicara', 20, 4, 'September', 2025),
(2, 'Paket Okupasi', 20, 4, 'September', 2025);

-- =====================================================
-- TABEL ABSENSI
-- =====================================================
CREATE TABLE IF NOT EXISTS absensi (
    absensi_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    terapis_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('hadir','izin','sakit','alpa') NOT NULL,
    catatan TEXT,
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE,
    FOREIGN KEY (terapis_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Contoh absensi
INSERT IGNORE INTO absensi (anak_id, terapis_id, tanggal, status, catatan) VALUES
(1, 1, '2025-09-01', 'hadir', 'Pertemuan pertama'),
(1, 1, '2025-09-03', 'izin', 'Sakit flu'),
(2, 2, '2025-09-02', 'hadir', 'Fokus cukup baik');

-- =====================================================
-- TABEL JADWAL
-- =====================================================
CREATE TABLE IF NOT EXISTS jadwal (
    jadwal_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    terapis_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam TIME NOT NULL,
    sesi VARCHAR(50),
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE,
    FOREIGN KEY (terapis_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Contoh jadwal
INSERT IGNORE INTO jadwal (anak_id, terapis_id, tanggal, jam, sesi) VALUES
(1, 1, '2025-09-05', '09:00:00', 'Sesi 1'),
(1, 1, '2025-09-07', '10:00:00', 'Sesi 2'),
(2, 2, '2025-09-06', '13:00:00', 'Sesi 1');

-- =====================================================
-- TABEL LAPORAN (file PDF upload)
-- =====================================================
CREATE TABLE IF NOT EXISTS laporan (
    laporan_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    terapis_id INT NOT NULL,
    pertemuan_ke INT NOT NULL,
    judul VARCHAR(200),
    file_path VARCHAR(255), -- lokasi file PDF
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE,
    FOREIGN KEY (terapis_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Contoh laporan
INSERT IGNORE INTO laporan (anak_id, terapis_id, pertemuan_ke, judul, file_path) VALUES
(1, 1, 1, 'Laporan Pertemuan 1 - Ayu', 'uploads/laporan/ayu_pertemuan1.pdf'),
(2, 2, 1, 'Laporan Pertemuan 1 - Bima', 'uploads/laporan/bima_pertemuan1.pdf');
