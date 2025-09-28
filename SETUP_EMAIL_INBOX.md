# Setup Email Masuk Inbox - Panduan Lengkap

## 🎯 Tujuan
Agar email reset password benar-benar masuk ke inbox Gmail, bukan spam atau tidak terkirim.

## 📋 Langkah-langkah Setup

### 1. Setup Gmail App Password

1. **Buka Gmail** → Klik profil → **Manage your Google Account**
2. **Security** → **2-Step Verification** → Aktifkan jika belum
3. **App passwords** → **Select app** → **Mail**
4. **Select device** → **Other** → Masukkan "Labirin App"
5. **Copy password** yang dihasilkan (16 karakter)

### 2. Konfigurasi File SMTP

Edit file `backend/simple_smtp.php`:

```php
$SMTP_SETTINGS = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com', // Ganti dengan email Gmail Anda
    'smtp_password' => 'your-app-password', // Ganti dengan App Password dari step 1
    'smtp_secure' => 'tls',
    'from_name' => 'Labirin Children Center',
    'from_email' => 'your-email@gmail.com' // Sama dengan username
];
```

### 3. Test Konfigurasi

1. **Test SMTP**: `http://localhost/Projek/backend/simple_smtp.php?test=1`
2. **Cek Log**: `http://localhost/Projek/view_email_log.php`
3. **Cek Email Tersimpan**: `http://localhost/Projek/view_saved_emails.php`

### 4. Test Reset Password

1. **Akses**: `http://localhost/Projek/frontend/login.html`
2. **Klik**: "Lupa password?"
3. **Masukkan**: T001
4. **Cek Inbox**: rezkialya0909@gmail.com

## 🔧 Konfigurasi Alternatif

### Opsi 1: Gmail SMTP (Recommended)
```php
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_secure' => 'tls'
```

### Opsi 2: Gmail SMTP SSL
```php
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 465,
'smtp_secure' => 'ssl'
```

### Opsi 3: SMTP Lainnya
```php
// Outlook
'smtp_host' => 'smtp-mail.outlook.com',
'smtp_port' => 587,

// Yahoo
'smtp_host' => 'smtp.mail.yahoo.com',
'smtp_port' => 587,
```

## 🚀 Langkah Test

### 1. Test Basic SMTP
```
http://localhost/Projek/backend/simple_smtp.php?test=1
```

### 2. Test Reset Password
1. Login page → "Lupa password?"
2. Username: T001
3. Cek inbox: rezkialya0909@gmail.com

### 3. Verifikasi
- ✅ Email masuk ke inbox
- ✅ Link reset password berfungsi
- ✅ Password berhasil direset

## 📧 Email Test yang Tersedia

- **T001**: rezkialya0909@gmail.com
- **T002**: terapis2@labirin.com
- **O001**: orangtua1@labirin.com
- **O002**: orangtua2@labirin.com

## 🔍 Troubleshooting

### Email Tidak Masuk Inbox

1. **Cek Spam Folder**
2. **Verifikasi App Password** (16 karakter)
3. **Cek 2FA** (harus aktif)
4. **Cek Log** untuk error detail

### Error "Authentication Failed"

1. Pastikan App Password benar
2. Pastikan 2FA aktif
3. Pastikan username adalah email Gmail lengkap

### Error "Connection Refused"

1. Cek koneksi internet
2. Cek firewall
3. Coba port 465 (SSL)

### Email Masuk Spam

1. **Tambahkan ke Contacts**
2. **Mark as Not Spam**
3. **Reply email** untuk meningkatkan reputation

## 💡 Tips Sukses

1. **Gunakan email Gmail yang aktif**
2. **Simpan App Password dengan aman**
3. **Test dengan email yang berbeda**
4. **Cek log untuk debugging**
5. **Pastikan 2FA aktif**

## 🎉 Hasil yang Diharapkan

Setelah setup selesai:
- ✅ Email reset password masuk ke inbox
- ✅ User bisa klik link di email
- ✅ Reset password berfungsi normal
- ✅ Sistem profesional dan user-friendly

## 📱 Mobile Testing

Untuk test di mobile:
1. Buka Gmail app
2. Cek inbox dan spam
3. Klik link reset password
4. Test reset password

---

**Catatan**: Setup ini memerlukan email Gmail yang valid dan App Password yang benar. Pastikan konfigurasi sudah tepat sebelum testing.
