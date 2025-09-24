# Instalasi Database Labirin untuk XAMPP

## Langkah-langkah Instalasi

### 1. Persiapan XAMPP
- Pastikan XAMPP sudah terinstall dan running
- Start Apache dan MySQL di XAMPP Control Panel
- Buka phpMyAdmin: http://localhost/phpmyadmin

### 2. Import Database
- Buka phpMyAdmin
- Klik tab "Import"
- Pilih file `database/labirin_db.sql`
- Klik "Go" untuk import

### 3. Verifikasi Database
- Database `labirin_db` akan dibuat otomatis
- Tabel yang tersedia:
  - `users` - Data pengguna (terapis & orangtua)
  - `anak` - Data anak
  - `paket_belajar` - Paket terapi
  - `absensi` - Data kehadiran
  - `jadwal` - Jadwal terapi
  - `laporan` - Laporan PDF

### 4. Kredensial Login
- **Terapis**: 
  - Username: `T001` atau `T002`
  - Password: `password_terapis`
- **Orangtua**: 
  - Username: `O001` atau `O002`
  - Password: `password_ortu`

### 5. Test Koneksi
- Buka: http://localhost/cek%20web%20labirin/check_login.php
- Atau test login langsung di aplikasi

## Troubleshooting

### Jika ada error charset:
- Pastikan MySQL menggunakan charset `utf8`
- Database sudah dibuat dengan charset yang benar

### Jika login tidak berfungsi:
- Pastikan password hash sudah benar
- Cek file `backend/config.php` untuk koneksi database

### Jika ada error foreign key:
- Pastikan semua tabel dibuat dengan engine `InnoDB`
- Cek urutan pembuatan tabel (users dulu, baru tabel lain)

## Struktur Database

Database ini sudah dioptimalkan untuk XAMPP dengan:
- Charset: `utf8` (bukan utf8mb4)
- Collation: `utf8_general_ci`
- Engine: `InnoDB` untuk semua tabel
- Password sudah di-hash dengan `PASSWORD_BCRYPT`
