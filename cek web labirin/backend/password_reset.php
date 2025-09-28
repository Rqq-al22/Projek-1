<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = read_json_body();

// Pastikan kolom email & tabel token tersedia (idempotent)
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(160) NULL");
} catch (Throwable $e) { /* abaikan jika MySQL lama tidak dukung IF NOT EXISTS */ }
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
} catch (Throwable $e) { /* abaikan */ }

if ($method === 'POST') {
    $username = trim((string)($body['username'] ?? ''));
    if ($username === '') {
        json_response(400, ['ok' => false, 'error' => 'MISSING_USERNAME']);
    }
    $stmt = $pdo->prepare('SELECT user_id, username, email FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if (!$u) {
        json_response(200, ['ok' => true]);
    }
    $toEmail = (string)($u['email'] ?? '');
    if ($toEmail === '') {
        json_response(200, ['ok' => true]);
    }
    $token = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)')
        ->execute([(int)$u['user_id'], $token, $expiresAt]);
    $base = $GLOBALS['APP_URL'] ?? 'http://localhost/cek%20web%20labirin';
    $resetLink = rtrim($base, '/') . '/frontend/reset.html?token=' . urlencode($token);
    $html = '<p>Anda meminta reset password untuk akun: <b>' . htmlspecialchars((string)$u['username']) . '</b></p>'
          . '<p>Silakan klik tautan berikut untuk mengatur ulang kata sandi (berlaku 1 jam):</p>'
          . '<p><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>';
    send_email_simple($toEmail, 'Reset Password Labirin', $html);
    json_response(200, ['ok' => true]);
}

if ($method === 'PUT') {
    $token = trim((string)($body['token'] ?? ''));
    $newPass = (string)($body['new_password'] ?? '');
    if ($token === '' || $newPass === '') {
        json_response(400, ['ok' => false, 'error' => 'MISSING_FIELDS']);
    }
    $stmt = $pdo->prepare('SELECT user_id FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(400, ['ok' => false, 'error' => 'INVALID_TOKEN']);
    }
    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?')->execute([$hash, (int)$row['user_id']]);
    $pdo->prepare('DELETE FROM password_reset_tokens WHERE token = ?')->execute([$token]);
    json_response(200, ['ok' => true]);
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
