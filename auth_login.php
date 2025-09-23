<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$body = read_json_body();
$login = trim((string)($body['login'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($login === '' || $password === '') {
    json_response(400, ['ok' => false, 'error' => 'MISSING_CREDENTIALS']);
}

$stmt = $pdo->prepare('SELECT id, id_code, name, email, phone, role, password_hash FROM users WHERE email = ? OR name = ? OR id_code = ? LIMIT 1');
$stmt->execute([$login, $login, $login]);
$user = $stmt->fetch();

if (!$user || !password_matches($password, (string)$user['password_hash'])) {
    json_response(401, ['ok' => false, 'error' => 'INVALID_LOGIN']);
}

$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'id_code' => (string)$user['id_code'],
    'name' => (string)$user['name'],
    'email' => (string)$user['email'],
    'phone' => (string)($user['phone'] ?? ''),
    'role' => (string)$user['role'],
];

json_response(200, ['ok' => true, 'user' => $_SESSION['user']]);
