<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = require_login();

if ($method === 'GET') {
    if (is_parent_role($user)) {
        $childId = get_child_id_for_parent($pdo, (int)$user['id']);
        if (!$childId) {
            json_response(200, ['ok' => true, 'items' => []]);
        }
        $stmt = $pdo->prepare('SELECT jadwal_id as id, anak_id as child_id, terapis_id as therapist_user_id, tanggal as day_of_week, jam as time_start, sesi as room, NULL as notes FROM jadwal WHERE anak_id = ? ORDER BY tanggal, jam');
        $stmt->execute([$childId]);
        json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
    } else {
        $stmt = $pdo->query('SELECT j.jadwal_id as id, j.anak_id as child_id, an.nama_anak as child_name, j.terapis_id as therapist_user_id, j.tanggal as day_of_week, j.jam as time_start, NULL as time_end, j.sesi as room, NULL as notes FROM jadwal j JOIN anak an ON an.anak_id = j.anak_id ORDER BY j.tanggal, j.jam');
        json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
    }
}

if ($method === 'POST') {
    require_role_staff($user);
    $body = read_json_body();
    $childId = (int)($body['child_id'] ?? 0);
    $therapistId = (int)($body['therapist_user_id'] ?? (int)$user['id']);
    $tanggal = (string)($body['day_of_week'] ?? date('Y-m-d'));
    $jam = (string)($body['time_start'] ?? '09:00:00');
    $sesi = trim((string)($body['room'] ?? 'Sesi 1')); 
    if ($childId <= 0) {
        json_response(400, ['ok' => false, 'error' => 'MISSING_CHILD_ID']);
    }
    $pdo->prepare('INSERT INTO jadwal (anak_id, terapis_id, tanggal, jam, sesi) VALUES (?, ?, ?, ?, ?)')
        ->execute([$childId, $therapistId, $tanggal, $jam, $sesi]);
    json_response(200, ['ok' => true]);
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
