<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');

if (session_status() === PHP_SESSION_NONE) {
    // Gunakan nama sesi khusus agar tidak bentrok dengan aplikasi lain di localhost/phpMyAdmin
    if (session_name() !== 'LABIRINSESSID') {
        session_name('LABIRINSESSID');
    }
    // Opsional: atur parameter cookie sesi
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$DB_HOST = getenv('LABIRIN_DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('LABIRIN_DB_NAME') ?: 'labirin_db';
$DB_USER = getenv('LABIRIN_DB_USER') ?: 'root';
$DB_PASS = getenv('LABIRIN_DB_PASS') ?: '';

// SMTP sederhana (gunakan mail() atau relay lokal XAMPP). Jika Anda memakai SMTP pihak ketiga,
// set env berikut dan pastikan php.ini/sendmail sudah dikonfigurasi.
// Contoh env: LABIRIN_SMTP_FROM, LABIRIN_APP_URL
$APP_URL = getenv('LABIRIN_APP_URL') ?: 'http://localhost/cek%20web%20labirin';
$SMTP_FROM = getenv('LABIRIN_SMTP_FROM') ?: 'no-reply@localhost';

// Opsional: konfigurasi SMTP eksplisit. Jika Anda ingin menggunakan SMTP terautentikasi,
// set environment berikut pada XAMPP anda (httpd/apache env atau .htaccess PHP):
// LABIRIN_SMTP_HOST, LABIRIN_SMTP_PORT, LABIRIN_SMTP_USER, LABIRIN_SMTP_PASS, LABIRIN_SMTP_SECURE
$SMTP_HOST = getenv('LABIRIN_SMTP_HOST') ?: '';
$SMTP_PORT = getenv('LABIRIN_SMTP_PORT') ?: '';
$SMTP_USER = getenv('LABIRIN_SMTP_USER') ?: '';
$SMTP_PASS = getenv('LABIRIN_SMTP_PASS') ?: '';
$SMTP_SECURE = getenv('LABIRIN_SMTP_SECURE') ?: '';

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
    // Migration ringan agar login tidak gagal jika kolom baru belum ada
    try { $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(160) NULL"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('male','female') NULL"); } catch (Throwable $e) {}
    try { $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB"); } catch (Throwable $e) {}
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
    return isset($user['role']) && $user['role'] === 'orangtua';
}

function is_staff_role(array $user): bool {
    return isset($user['role']) && $user['role'] === 'terapis';
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

function send_email_simple(string $toEmail, string $subject, string $message): void {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $from = $GLOBALS['SMTP_FROM'] ?? 'no-reply@localhost';
    $headers[] = 'From: ' . $from;
    $ok = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
    if (!$ok) {
        error_log("[EMAIL_FALLBACK_LOG] to={$toEmail} subject={$subject} message=" . str_replace(["\n", "\r"], ' ', $message));
    }
}

function send_whatsapp_stub(string $phone, string $message): void {
    error_log("[WHATSAPP_STUB] to={$phone} message=" . str_replace(["\n", "\r"], ' ', $message));
}

function get_child_id_for_parent(PDO $pdo, int $userId): ?int {
    $stmt = $pdo->prepare('SELECT a.anak_id FROM anak a WHERE a.orangtua_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? intval($row['anak_id']) : null;
}

function ensure_upload_dir(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}
