<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$body = read_json_body();
$login = trim((string)($body['login'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($login === '' || $password === '') {
    json_response(400, ['ok' => false, 'error' => 'MISSING_CREDENTIALS']);
}

$stmt = $pdo->prepare('SELECT user_id, username, password, role, nama_lengkap, gender FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$login]);
$user = $stmt->fetch();

if (!$user || !password_matches($password, (string)$user['password'])) {
    json_response(401, ['ok' => false, 'error' => 'INVALID_LOGIN']);
}

$_SESSION['user'] = [
    'id' => (int)$user['user_id'],
    'username' => (string)$user['username'],
    'name' => (string)$user['nama_lengkap'],
    'role' => (string)$user['role'],
    'gender' => (string)($user['gender'] ?? '')
];

json_response(200, ['ok' => true, 'user' => $_SESSION['user']]);
