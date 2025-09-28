<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = require_login();

if ($method === 'GET') {
    // Parent: lihat terapis yang ditugaskan
    if (is_parent_role($user)) {
        $stmt = $pdo->prepare(
            'SELECT u.user_id as id, u.username, u.nama_lengkap, u.email 
             FROM parent_therapist pt 
             JOIN users u ON u.user_id = pt.terapis_id 
             WHERE pt.orangtua_id = ?'
        );
        $stmt->execute([(int)$user['id']]);
        $rows = $stmt->fetchAll();
        // fallback: jika belum ada relasi eksplisit, ambil dari jadwal pertama anak
        if (!$rows) {
            $childId = get_child_id_for_parent($pdo, (int)$user['id']);
            if ($childId) {
                $q = $pdo->prepare('SELECT DISTINCT u.user_id as id, u.username, u.nama_lengkap, u.email 
                    FROM jadwal j JOIN users u ON u.user_id = j.terapis_id WHERE j.anak_id = ? LIMIT 3');
                $q->execute([$childId]);
                $rows = $q->fetchAll();
            }
        }
        json_response(200, ['ok' => true, 'therapists' => $rows]);
    }
    // Staff: bisa lihat daftar orangtua-terapis (opsional filter)
    require_role_staff($user);
    $parentId = (int)($_GET['parent_id'] ?? 0);
    $sql = 'SELECT pt.id, p.user_id as parent_id, p.nama_lengkap as parent_name, t.user_id as therapist_id, t.nama_lengkap as therapist_name
            FROM parent_therapist pt 
            JOIN users p ON p.user_id = pt.orangtua_id 
            JOIN users t ON t.user_id = pt.terapis_id';
    $params = [];
    if ($parentId > 0) { $sql .= ' WHERE p.user_id = ?'; $params[] = $parentId; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    // Staff only: assign terapis ke orangtua
    require_role_staff($user);
    $body = read_json_body();
    $parentId = (int)($body['parent_id'] ?? 0);
    $therapistId = (int)($body['therapist_id'] ?? 0);
    if ($parentId <= 0 || $therapistId <= 0) {
        json_response(400, ['ok' => false, 'error' => 'MISSING_FIELDS']);
    }
    $pdo->prepare('INSERT IGNORE INTO parent_therapist (orangtua_id, terapis_id) VALUES (?, ?)')
        ->execute([$parentId, $therapistId]);
    json_response(200, ['ok' => true]);
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);


