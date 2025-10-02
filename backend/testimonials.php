<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/* ========================================
   TESTIMONIALS API - API untuk mengelola testimoni
   ======================================== */

// Fungsi untuk sanitasi input
function sanitize_input(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk validasi rating
function validate_rating($rating): bool {
    return is_numeric($rating) && $rating >= 1 && $rating <= 5;
}

// Fungsi untuk rate limiting sederhana
function check_rate_limit(PDO $pdo, string $ip): bool {
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count 
        FROM testimoni 
        WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ');
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    return intval($result['count']) < 3; // Maksimal 3 testimoni per jam per IP
}

// Fungsi untuk mendapatkan IP address
function get_client_ip(): string {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

// Fungsi untuk mendapatkan semua testimoni yang sudah approved
function get_approved_testimonials(PDO $pdo): array {
    $stmt = $pdo->prepare('
        SELECT testimoni_id, nama, rating, caption, created_at
        FROM testimoni 
        WHERE status = "approved" 
        ORDER BY created_at DESC 
        LIMIT 20
    ');
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fungsi untuk menyimpan testimoni baru
function save_testimonial(PDO $pdo, array $data): array {
    try {
        $pdo->beginTransaction();
        
        // Validasi input
        $nama = sanitize_input($data['nama'] ?? '');
        $rating = intval($data['rating'] ?? 0);
        $caption = sanitize_input($data['caption'] ?? '');
        
        if (empty($nama) || empty($caption)) {
            throw new Exception('Nama dan testimoni harus diisi');
        }
        
        if (!validate_rating($rating)) {
            throw new Exception('Rating harus berupa angka 1-5');
        }
        
        if (strlen($caption) < 10) {
            throw new Exception('Testimoni terlalu pendek (minimal 10 karakter)');
        }
        
        if (strlen($caption) > 500) {
            throw new Exception('Testimoni terlalu panjang (maksimal 500 karakter)');
        }
        
        $ip = get_client_ip();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Cek rate limiting
        if (!check_rate_limit($pdo, $ip)) {
            throw new Exception('Terlalu banyak testimoni dalam 1 jam. Silakan coba lagi nanti.');
        }
        
        // Simpan ke database
        $stmt = $pdo->prepare('
            INSERT INTO testimoni (nama, rating, caption, ip_address, user_agent, status) 
            VALUES (?, ?, ?, ?, ?, "pending")
        ');
        
        $stmt->execute([$nama, $rating, $caption, $ip, $userAgent]);
        
        $pdo->commit();
        
        return [
            'ok' => true,
            'message' => 'Testimoni berhasil dikirim! Testimoni Anda akan ditampilkan setelah disetujui oleh admin.',
            'testimoni_id' => $pdo->lastInsertId()
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'ok' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Fungsi untuk admin: mendapatkan semua testimoni
function get_all_testimonials(PDO $pdo): array {
    $stmt = $pdo->prepare('
        SELECT t.*, u.nama_lengkap as approved_by_name
        FROM testimoni t
        LEFT JOIN users u ON t.approved_by = u.user_id
        ORDER BY t.created_at DESC
    ');
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fungsi untuk admin: approve testimoni
function approve_testimonial(PDO $pdo, int $testimoniId, int $adminId): array {
    try {
        $stmt = $pdo->prepare('
            UPDATE testimoni 
            SET status = "approved", approved_at = NOW(), approved_by = ?
            WHERE testimoni_id = ?
        ');
        $stmt->execute([$adminId, $testimoniId]);
        
        return ['ok' => true, 'message' => 'Testimoni berhasil disetujui'];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

// Fungsi untuk admin: reject testimoni
function reject_testimonial(PDO $pdo, int $testimoniId, int $adminId): array {
    try {
        $stmt = $pdo->prepare('
            UPDATE testimoni 
            SET status = "rejected", approved_by = ?
            WHERE testimoni_id = ?
        ');
        $stmt->execute([$adminId, $testimoniId]);
        
        return ['ok' => true, 'message' => 'Testimoni ditolak'];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/* ========================================
   REQUEST HANDLING - Menangani request
   ======================================== */

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // Admin: ambil semua testimoni
                $user = require_login();
                require_role_staff($user);
                $testimonials = get_all_testimonials($pdo);
                json_response(200, ['ok' => true, 'data' => $testimonials]);
            } else {
                // Public: ambil testimoni yang sudah approved
                $testimonials = get_approved_testimonials($pdo);
                json_response(200, ['ok' => true, 'data' => $testimonials]);
            }
            break;
            
        case 'POST':
            if ($action === 'submit') {
                // Public: submit testimoni baru
                $data = read_json_body();
                $result = save_testimonial($pdo, $data);
                json_response($result['ok'] ? 201 : 400, $result);
            } elseif ($action === 'approve') {
                // Admin: approve testimoni
                $user = require_login();
                require_role_staff($user);
                $data = read_json_body();
                $testimoniId = intval($data['testimoni_id'] ?? 0);
                $result = approve_testimonial($pdo, $testimoniId, $user['user_id']);
                json_response($result['ok'] ? 200 : 400, $result);
            } elseif ($action === 'reject') {
                // Admin: reject testimoni
                $user = require_login();
                require_role_staff($user);
                $data = read_json_body();
                $testimoniId = intval($data['testimoni_id'] ?? 0);
                $result = reject_testimonial($pdo, $testimoniId, $user['user_id']);
                json_response($result['ok'] ? 200 : 400, $result);
            } else {
                json_response(400, ['ok' => false, 'error' => 'Invalid action']);
            }
            break;
            
        default:
            json_response(405, ['ok' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("[TESTIMONIALS_API_ERROR] " . $e->getMessage());
    json_response(500, ['ok' => false, 'error' => 'Internal server error']);
}
