<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = read_json_body();

if ($method === 'POST') {
    $email = trim((string)($body['email'] ?? ''));
    if ($email === '') {
        json_response(400, ['ok' => false, 'error' => 'MISSING_EMAIL']);
    }
    $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if (!$u) {
        json_response(200, ['ok' => true]);
    }
    $token = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)')
        ->execute([(int)$u['id'], $token, $expiresAt]);
    $resetLink = 'http://localhost/cek%20web%20labirin/frontend/reset.html?token=' . urlencode($token);
    send_email_stub($email, 'Reset Password Labirin', "Klik tautan ini untuk reset: {$resetLink}");
    json_response(200, ['ok' => true]);
}

if ($method === 'PUT') {
    $token = trim((string)($body['token'] ?? ''));
    $newPass = (string)($body['new_password'] ?? '');
    if ($token === '' || $newPass === '') {
        json_response(400, ['ok' => false, 'error' => 'MISSING_FIELDS']);
    }
    $stmt = $pdo->prepare('SELECT prt.user_id FROM password_reset_tokens prt WHERE prt.token = ? AND prt.expires_at > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(400, ['ok' => false, 'error' => 'INVALID_TOKEN']);
    }
    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, (int)$row['user_id']]);
    $pdo->prepare('DELETE FROM password_reset_tokens WHERE token = ?')->execute([$token]);
    json_response(200, ['ok' => true]);
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
