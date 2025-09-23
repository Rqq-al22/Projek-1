<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = require_login();

if ($method === 'POST') {
    require_role_staff($user);
    $body = read_json_body();
    $childId = (int)($body['child_id'] ?? 0);
    $status = (string)($body['status'] ?? 'present'); // present | izin | alpha
    $note = trim((string)($body['note'] ?? ''));
    if ($childId <= 0) {
        json_response(400, ['ok' => false, 'error' => 'MISSING_CHILD_ID']);
    }
    $pdo->prepare('INSERT INTO attendance (child_id, staff_user_id, status, note) VALUES (?, ?, ?, ?)')
        ->execute([$childId, (int)$user['id'], $status, $note]);

    // cek batas izin > 4 pada bulan berjalan
    if ($status === 'izin') {
        $q = $pdo->prepare("SELECT COUNT(*) AS cnt FROM attendance WHERE child_id = ? AND status = 'izin' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
        $q->execute([$childId]);
        $cnt = (int)($q->fetch()['cnt'] ?? 0);
        if ($cnt > 4) {
            $info = $pdo->prepare('SELECT p.email, p.phone FROM children c JOIN users p ON p.id = c.parent_user_id WHERE c.id = ? LIMIT 1');
            $info->execute([$childId]);
            $row = $info->fetch();
            if ($row) {
                send_email_stub((string)$row['email'], 'Batas Izin Terlampaui', 'Izin bulan ini melebihi 4x.');
                if (!empty($row['phone'])) {
                    send_whatsapp_stub((string)$row['phone'], 'Izin bulan ini melebihi 4x.');
                }
            }
        }
    }

    json_response(200, ['ok' => true]);
}

if ($method === 'GET') {
    if (is_parent_role($user)) {
        $childId = get_child_id_for_parent($pdo, (int)$user['id']);
        if (!$childId) {
            json_response(200, ['ok' => true, 'items' => []]);
        }
        $stmt = $pdo->prepare('SELECT id, status, note, created_at FROM attendance WHERE child_id = ? ORDER BY created_at DESC LIMIT 200');
        $stmt->execute([$childId]);
        json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
    } else {
        // staff: bisa filter by child_id
        $childId = (int)($_GET['child_id'] ?? 0);
        $sql = 'SELECT a.id, a.child_id, a.status, a.note, a.created_at, c.name as child_name FROM attendance a JOIN children c ON c.id = a.child_id';
        $params = [];
        if ($childId > 0) {
            $sql .= ' WHERE a.child_id = ?';
            $params[] = $childId;
        }
        $sql .= ' ORDER BY a.created_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
    }
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
