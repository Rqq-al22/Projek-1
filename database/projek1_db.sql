-- =====================================================
-- Buat database
-- =====================================================
CREATE DATABASE IF NOT EXISTS projek1_db;
USE projek1_db;

-- =====================================================
-- TABEL USERS (login: orangtua & terapis)
-- =====================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('terapis','orangtua') NOT NULL,
    nama_lengkap VARCHAR(100),
    reset_token VARCHAR(255) NULL,
    reset_token_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contoh data awal
INSERT INTO users (username, password, email, role, nama_lengkap) VALUES
('T001', 'Oke2222', 'rezkialya0909@gmail.com', 'terapis', 'Budi Terapis'),
('T002', 'Oke3333', 'terapis2@labirin.com', 'terapis', 'Sinta Terapis'),
('O001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'orangtua1@labirin.com', 'orangtua', 'Ibu Sari'),
('O002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'orangtua2@labirin.com', 'orangtua', 'Pak Andi');

-- =====================================================
-- TABEL LINK ORANGTUA-TERAPIS (relasi eksplisit)
-- =====================================================
CREATE TABLE parent_therapist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orangtua_id INT NOT NULL,
    terapis_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_parent_therapist (orangtua_id, terapis_id),
    FOREIGN KEY (orangtua_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (terapis_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Contoh relasi: O001 ditangani T001, O002 ditangani T002
INSERT INTO parent_therapist (orangtua_id, terapis_id) VALUES (3,1),(4,2);

-- =====================================================
-- TABEL PASSWORD RESETS (untuk reset password)
-- =====================================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expired_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL ANAK (anak milik orangtua)
-- =====================================================
CREATE TABLE anak (
    anak_id INT AUTO_INCREMENT PRIMARY KEY,
    orangtua_id INT NOT NULL,
    nama_anak VARCHAR(100) NOT NULL,
    tanggal_lahir DATE,
    keterangan TEXT,
    FOREIGN KEY (orangtua_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Contoh data anak
INSERT INTO anak (orangtua_id, nama_anak, tanggal_lahir, keterangan) VALUES
(3, 'Ayu', '2018-05-10', 'Perlu terapi wicara'),
(4, 'Bima', '2017-11-22', 'Terapi okupasi');

-- =====================================================
-- TABEL PAKET BELAJAR (misal 20x pertemuan per bulan)
-- =====================================================
CREATE TABLE paket_belajar (
    paket_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    nama_paket VARCHAR(100),
    jumlah_pertemuan INT DEFAULT 20,
    max_reschedule INT DEFAULT 4, -- izin maksimal
    bulan VARCHAR(20),
    tahun INT,
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE
);

-- Contoh paket belajar
INSERT INTO paket_belajar (anak_id, nama_paket, jumlah_pertemuan, max_reschedule, bulan, tahun) VALUES
(1, 'Paket Wicara', 20, 4, 'September', 2025),
(2, 'Paket Okupasi', 20, 4, 'September', 2025);

-- =====================================================
-- TABEL ABSENSI
-- =====================================================
CREATE TABLE absensi (
    absensi_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    terapis_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('hadir','izin','sakit','alpa') NOT NULL,
    catatan TEXT,
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE,
    FOREIGN KEY (terapis_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Contoh absensi
INSERT INTO absensi (anak_id, terapis_id, tanggal, status, catatan) VALUES
(1, 1, '2025-09-01', 'hadir', 'Pertemuan pertama'),
(1, 1, '2025-09-03', 'izin', 'Sakit flu'),
(2, 2, '2025-09-02', 'hadir', 'Fokus cukup baik');

-- =====================================================
-- TABEL JADWAL
-- =====================================================
CREATE TABLE jadwal (
    jadwal_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    terapis_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam TIME NOT NULL,
    sesi VARCHAR(50),
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE,
    FOREIGN KEY (terapis_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Contoh jadwal
INSERT INTO jadwal (anak_id, terapis_id, tanggal, jam, sesi) VALUES
(1, 1, '2025-09-05', '09:00:00', 'Sesi 1'),
(1, 1, '2025-09-07', '10:00:00', 'Sesi 2'),
(2, 2, '2025-09-06', '13:00:00', 'Sesi 1');

-- =====================================================
-- TABEL LAPORAN (file PDF upload)
-- =====================================================
CREATE TABLE laporan (
    laporan_id INT AUTO_INCREMENT PRIMARY KEY,
    anak_id INT NOT NULL,
    terapis_id INT NOT NULL,
    pertemuan_ke INT NOT NULL,
    judul VARCHAR(200),
    file_path VARCHAR(255), -- lokasi file PDF
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anak_id) REFERENCES anak(anak_id) ON DELETE CASCADE,
    FOREIGN KEY (terapis_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Contoh laporan
INSERT INTO laporan (anak_id, terapis_id, pertemuan_ke, judul, file_path) VALUES
(1, 1, 1, 'Laporan Pertemuan 1 - Ayu', 'uploads/laporan/ayu_pertemuan1.pdf'),
(2, 2, 1, 'Laporan Pertemuan 1 - Bima', 'uploads/laporan/bima_pertemuan1.pdf');
