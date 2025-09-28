# Setup Gmail SMTP untuk Email Reset Password

## 🎯 Tujuan
Agar email reset password benar-benar masuk ke inbox user, bukan hanya tersimpan di file.

## 📋 Langkah-langkah Setup

### 1. Buat App Password di Gmail

1. **Buka Gmail** → Settings → Security
2. **Aktifkan 2-Factor Authentication** (jika belum)
3. **Buat App Password**:
   - Klik "App passwords"
   - Pilih "Mail" dan "Other"
   - Masukkan nama: "Labirin App"
   - Copy password yang dihasilkan (16 karakter)

### 2. Konfigurasi File SMTP

Edit file `backend/smtp_config.php`:

```php
$SMTP_CONFIG = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-email@gmail.com', // Ganti dengan email Gmail Anda
    'password' => 'your-app-password', // Ganti dengan App Password dari step 1
    'from_name' => 'Labirin Children Center',
    'from_email' => 'your-email@gmail.com' // Sama dengan username
];
```

### 3. Update Password Reset

Edit file `backend/password_reset.php`, ganti bagian email:

```php
// Ganti baris ini:
$emailSent = $emailSender->send($toEmail, 'Reset Password - Labirin Children Center', $html);

// Dengan ini:
require_once __DIR__ . '/smtp_config.php';
$emailSent = send_email_smtp($toEmail, 'Reset Password - Labirin Children Center', $html);
```

### 4. Test Konfigurasi

1. **Test SMTP**: `http://localhost/Projek/backend/smtp_config.php?test=1`
2. **Test Reset Password**: Klik "Lupa password?" di login
3. **Cek Inbox**: Email harus masuk ke inbox Gmail

## 🔧 Alternatif: Menggunakan PHPMailer

### Install PHPMailer

1. **Download PHPMailer**:
   ```
   composer require phpmailer/phpmailer
   ```

2. **Atau manual download**:
   - Download dari: https://github.com/PHPMailer/PHPMailer
   - Extract ke folder `backend/vendor/`

### Konfigurasi PHPMailer

Edit file `backend/smtp_gmail.php` dan update konfigurasi:

```php
private $smtp_username = 'your-email@gmail.com';
private $smtp_password = 'your-app-password';
```

## 🚀 Langkah Test

### 1. Test Basic SMTP
```
http://localhost/Projek/backend/smtp_config.php?test=1
```

### 2. Test Reset Password
1. Akses: `http://localhost/Projek/frontend/login.html`
2. Klik "Lupa password?"
3. Masukkan username: T001
4. Cek inbox: rezkialya0909@gmail.com

### 3. Cek Log
```
http://localhost/Projek/view_email_log.php
```

## 📧 Email yang Tersedia untuk Test

- **T001**: rezkialya0909@gmail.com
- **T002**: terapis2@labirin.com
- **O001**: orangtua1@labirin.com
- **O002**: orangtua2@labirin.com

## 🔍 Troubleshooting

### Email Tidak Masuk Inbox

1. **Cek Spam Folder**
2. **Cek App Password** (harus benar)
3. **Cek 2FA** (harus aktif)
4. **Cek Log** untuk error detail

### Error "Authentication Failed"

1. Pastikan App Password benar
2. Pastikan 2FA aktif
3. Pastikan username adalah email Gmail

### Error "Connection Refused"

1. Cek koneksi internet
2. Cek firewall
3. Coba port 465 (SSL) sebagai alternatif

## 💡 Tips

1. **Gunakan email Gmail yang aktif**
2. **Simpan App Password dengan aman**
3. **Test dengan email yang berbeda**
4. **Cek log untuk debugging**

## 🎉 Hasil yang Diharapkan

Setelah setup selesai:
- ✅ Email reset password masuk ke inbox
- ✅ User bisa klik link di email
- ✅ Reset password berfungsi normal
- ✅ Tidak perlu akses manual ke file

---

**Catatan**: Setup ini memerlukan email Gmail yang valid dan App Password yang benar. Pastikan konfigurasi sudah tepat sebelum testing.
