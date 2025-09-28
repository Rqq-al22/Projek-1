# Instalasi Database Labirin untuk Laragon

## Langkah-langkah Instalasi

### 1. Persiapan Laragon
- Pastikan Laragon sudah terinstall dan running
- Start Apache dan MySQL di Laragon Control Panel
- Buka phpMyAdmin: http://localhost/phpmyadmin

### 2. Import Database
- Buka phpMyAdmin
- Klik tab "Import"
- Pilih file `database/projek1_db.sql`
- Klik "Go" untuk import

### 3. Verifikasi Database
- Database `projek1_db` akan dibuat otomatis
- Tabel yang tersedia:
  - `users` - Data pengguna (terapis & orangtua)
  - `anak` - Data anak
  - `paket_belajar` - Paket terapi
  - `absensi` - Data kehadiran
  - `jadwal` - Jadwal terapi
  - `laporan` - Laporan PDF

### 4. Kredensial Login
- **Terapis**: 
  - Username: `T001` - Password: `Oke2222`
  - Username: `T002` - Password: `Oke3333`
- **Orangtua**: 
  - Username: `O001` atau `O002`
  - Password: `password` (default hashed)

### 5. Test Koneksi
- Buka: http://localhost/Projek/check_login.php
- Atau test login langsung di aplikasi: http://localhost/Projek

## Konfigurasi Laragon

### Database Settings
- Host: `127.0.0.1` (default Laragon)
- Port: `3306` (default MySQL)
- Username: `root`
- Password: `` (kosong, default Laragon)
- Database: `projek1_db`

### Path Aplikasi
- URL Aplikasi: `http://localhost/Projek`
- Path Fisik: `C:\laragon\www\Projek`

## Troubleshooting

### Jika ada error charset:
- Pastikan MySQL menggunakan charset `utf8mb4`
- Database sudah dibuat dengan charset yang benar

### Jika login tidak berfungsi:
- Pastikan password hash sudah benar
- Cek file `backend/config.php` untuk koneksi database
- Pastikan Laragon Apache dan MySQL sudah running

### Jika ada error foreign key:
- Pastikan semua tabel dibuat dengan engine `InnoDB`
- Cek urutan pembuatan tabel (users dulu, baru tabel lain)

### Jika aplikasi tidak bisa diakses:
- Pastikan folder `Projek` ada di `C:\laragon\www\`
- Cek apakah Apache Laragon sudah running
- Coba restart Laragon jika perlu

## Struktur Database

Database ini sudah dioptimalkan untuk Laragon dengan:
- Charset: `utf8mb4` (default Laragon)
- Collation: `utf8mb4_unicode_ci`
- Engine: `InnoDB` untuk semua tabel
- Password sudah di-hash dengan `PASSWORD_BCRYPT`

## Fitur Laragon yang Digunakan

- **Apache**: Web server untuk menjalankan aplikasi PHP
- **MySQL**: Database server untuk menyimpan data
- **phpMyAdmin**: Interface web untuk mengelola database
- **Auto Virtual Host**: Laragon otomatis membuat virtual host untuk folder di `www`

## Konfigurasi Email untuk Reset Password

### Setup Email di Laragon
1. **Buka Laragon Control Panel**
2. **Klik 'Menu' > 'Apache' > 'php.ini'**
3. **Cari section `[mail function]` dan set:**
   ```ini
   sendmail_path = "C:\laragon\bin\sendmail\sendmail.exe -t"
   SMTP = localhost
   smtp_port = 25
   ```
4. **Restart Apache**

### Test Email System
- **Test Email**: `http://localhost/Projek/test_email.php`
- **Reset Password**: Klik "Lupa password?" di halaman login

### Email yang Tersedia untuk Test
- **T001**: `rezkialya0909@gmail.com`
- **T002**: `terapis2@labirin.com`
- **O001**: `orangtua1@labirin.com`
- **O002**: `orangtua2@labirin.com`

## Tips Laragon

1. **Auto Start**: Aktifkan auto start di Laragon untuk Apache dan MySQL
2. **Port**: Default Laragon menggunakan port 80 untuk Apache dan 3306 untuk MySQL
3. **SSL**: Laragon mendukung HTTPS dengan SSL certificate otomatis
4. **Multiple Projects**: Bisa menjalankan multiple project sekaligus di folder `www`
5. **Email**: Pastikan sendmail dikonfigurasi untuk fitur reset password
