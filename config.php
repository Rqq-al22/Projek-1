<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_HOST = getenv('LABIRIN_DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('LABIRIN_DB_NAME') ?: 'labirin_db';
$DB_USER = getenv('LABIRIN_DB_USER') ?: 'root';
$DB_PASS = getenv('LABIRIN_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB connection failed', 'detail' => $e->getMessage()]);
    exit;
}

function json_response(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): array {
    $user = current_user();
    if (!$user) {
        json_response(401, ['ok' => false, 'error' => 'UNAUTHORIZED']);
    }
    return $user;
}

function is_parent_role(array $user): bool {
    return isset($user['id_code']) && str_starts_with($user['id_code'], 'A1');
}

function is_staff_role(array $user): bool {
    return isset($user['id_code']) && str_starts_with($user['id_code'], 'L2');
}

function require_role_parent(array $user): void {
    if (!is_parent_role($user)) {
        json_response(403, ['ok' => false, 'error' => 'FORBIDDEN_PARENT_ONLY']);
    }
}

function require_role_staff(array $user): void {
    if (!is_staff_role($user)) {
        json_response(403, ['ok' => false, 'error' => 'FORBIDDEN_STAFF_ONLY']);
    }
}

function password_matches(string $plain, string $stored): bool {
    if (strlen($stored) >= 60 && str_starts_with($stored, '$2y$')) {
        return password_verify($plain, $stored);
    }
    return hash_equals($stored, $plain);
}

function send_email_stub(string $toEmail, string $subject, string $message): void {
    error_log("[EMAIL_STUB] to={$toEmail} subject={$subject} message=" . str_replace(["\n", "\r"], ' ', $message));
}

function send_whatsapp_stub(string $phone, string $message): void {
    error_log("[WHATSAPP_STUB] to={$phone} message=" . str_replace(["\n", "\r"], ' ', $message));
}

function get_child_id_for_parent(PDO $pdo, int $userId): ?int {
    $stmt = $pdo->prepare('SELECT c.id FROM children c WHERE c.parent_user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? intval($row['id']) : null;
}

function ensure_upload_dir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}
