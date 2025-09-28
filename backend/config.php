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
$DB_NAME = getenv('LABIRIN_DB_NAME') ?: 'projek1_db';
$DB_USER = getenv('LABIRIN_DB_USER') ?: 'root';
$DB_PASS = getenv('LABIRIN_DB_PASS') ?: '';

// SMTP sederhana (gunakan mail() atau relay lokal Laragon). Jika Anda memakai SMTP pihak ketiga,
// set env berikut dan pastikan php.ini/sendmail sudah dikonfigurasi.
// Contoh env: LABIRIN_SMTP_FROM, LABIRIN_APP_URL
$APP_URL = getenv('LABIRIN_APP_URL') ?: 'http://localhost/Projek';
$SMTP_FROM = getenv('LABIRIN_SMTP_FROM') ?: 'no-reply@localhost';

// Opsional: konfigurasi SMTP eksplisit. Jika Anda ingin menggunakan SMTP terautentikasi,
// set environment berikut pada Laragon anda (httpd/apache env atau .htaccess PHP):
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
    // Database sudah sesuai dengan struktur yang ada di projek1_db.sql
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

function send_email_simple(string $toEmail, string $subject, string $message): bool {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $from = $GLOBALS['SMTP_FROM'] ?? 'no-reply@localhost';
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    // Simpan ke file log untuk debugging
    $logFile = __DIR__ . '/email_log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - TO: {$toEmail} - SUBJECT: {$subject}\n";
    $logEntry .= "FROM: {$from}\n";
    $logEntry .= "MESSAGE PREVIEW: " . substr(strip_tags($message), 0, 100) . "...\n";
    $logEntry .= "---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Untuk Laragon, pastikan sendmail dikonfigurasi
    $ok = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
    
    if (!$ok) {
        error_log("[EMAIL_ERROR] Failed to send to={$toEmail} subject={$subject}");
        // Update log dengan status error
        $logEntry = date('Y-m-d H:i:s') . " - ERROR: Failed to send email\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    } else {
        error_log("[EMAIL_SUCCESS] Sent to={$toEmail} subject={$subject}");
        // Update log dengan status success
        $logEntry = date('Y-m-d H:i:s') . " - SUCCESS: Email sent successfully\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    return $ok;
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
