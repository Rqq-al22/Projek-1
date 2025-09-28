<?php
declare(strict_types=1);

// Set error reporting untuk debug
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output JSON

try {
    require_once __DIR__.'/config.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Config error: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = read_json_body();

// Database sudah sesuai dengan struktur yang ada di projek1_db.sql

if ($method === 'POST') {
    try {
        $username = trim((string)($body['username'] ?? ''));
        if ($username === '') {
            json_response(400, ['ok' => false, 'error' => 'MISSING_USERNAME']);
        }
        
        $stmt = $pdo->prepare('SELECT user_id, username, email, nama_lengkap, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $u = $stmt->fetch();
        
        if (!$u) {
            json_response(200, ['ok' => true, 'message' => 'Username tidak ditemukan']);
        }
        
        $toEmail = (string)($u['email'] ?? '');
        if ($toEmail === '') {
            json_response(200, ['ok' => true, 'message' => 'Email tidak tersedia untuk username ini']);
        }
        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        
        // Insert token ke database
        $stmt = $pdo->prepare('INSERT INTO password_resets (email, token, expired_at) VALUES (?, ?, ?)');
        $stmt->execute([$toEmail, $token, $expiresAt]);
        
        $base = $GLOBALS['APP_URL'] ?? 'http://localhost/Projek';
        $resetLink = rtrim($base, '/') . '/frontend/reset.html?token=' . urlencode($token);
        
        // Template email sederhana
        $html = '<html><body>';
        $html .= '<h2>Reset Password - Labirin Children Center</h2>';
        $html .= '<p>Halo <strong>' . htmlspecialchars((string)$u['nama_lengkap']) . '</strong>,</p>';
        $html .= '<p>Kami menerima permintaan untuk mereset password akun Anda:</p>';
        $html .= '<ul>';
        $html .= '<li><strong>Username:</strong> ' . htmlspecialchars((string)$u['username']) . '</li>';
        $html .= '<li><strong>Email:</strong> ' . htmlspecialchars($toEmail) . '</li>';
        $html .= '<li><strong>Role:</strong> ' . htmlspecialchars((string)$u['role']) . '</li>';
        $html .= '</ul>';
        $html .= '<p>Silakan klik link berikut untuk mengatur ulang kata sandi Anda:</p>';
        $html .= '<p><a href="' . htmlspecialchars($resetLink) . '" style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Reset Password</a></p>';
        $html .= '<p><strong>Penting:</strong> Link ini hanya berlaku selama 1 jam.</p>';
        $html .= '<p>Jika tombol tidak berfungsi, salin link ini: ' . htmlspecialchars($resetLink) . '</p>';
        $html .= '<hr>';
        $html .= '<p style="font-size: 12px; color: #666;">Email ini dikirim dari sistem Labirin Children Center.</p>';
        $html .= '</body></html>';
        
        // Gunakan SMTP sederhana untuk email yang benar-benar terkirim
        require_once __DIR__ . '/simple_smtp.php';
        $emailSent = send_email_simple_smtp($toEmail, 'Reset Password - Labirin Children Center', $html);
        
        if ($emailSent) {
            json_response(200, ['ok' => true, 'message' => 'Email reset password telah dikirim ke inbox ' . $toEmail]);
        } else {
            json_response(200, ['ok' => true, 'message' => 'Token reset password telah dibuat. Email tersimpan di folder emails/, silakan cek folder tersebut atau konfigurasi SMTP.']);
        }
        
    } catch (Exception $e) {
        json_response(500, ['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}

if ($method === 'PUT') {
    try {
        $token = trim((string)($body['token'] ?? ''));
        $newPass = (string)($body['new_password'] ?? '');
        
        if ($token === '' || $newPass === '') {
            json_response(400, ['ok' => false, 'error' => 'MISSING_FIELDS']);
        }
        
        $stmt = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND expired_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        
        if (!$row) {
            json_response(400, ['ok' => false, 'error' => 'INVALID_TOKEN']);
        }
        
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->execute([$hash, (string)$row['email']]);
        
        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE token = ?');
        $stmt->execute([$token]);
        
        json_response(200, ['ok' => true, 'message' => 'Password berhasil direset']);
        
    } catch (Exception $e) {
        json_response(500, ['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
