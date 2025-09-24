<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$user = require_login();

if (is_parent_role($user)) {
    $childId = get_child_id_for_parent($pdo, (int)$user['id']);
    if (!$childId) {
        json_response(200, ['ok' => true, 'items' => []]);
    }
    $stmt = $pdo->prepare('SELECT laporan_id as id, judul as caption, file_path, uploaded_at as meeting_date, uploaded_at as created_at FROM laporan WHERE anak_id = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$childId]);
    json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
}

// staff view (optional filter by child_id)
$childId = (int)($_GET['child_id'] ?? 0);
$sql = 'SELECT l.laporan_id as id, l.anak_id as child_id, a.nama_anak as child_name, l.judul as caption, l.file_path, l.uploaded_at as meeting_date, l.uploaded_at as created_at FROM laporan l JOIN anak a ON a.anak_id = l.anak_id';
$params = [];
if ($childId > 0) {
    $sql .= ' WHERE l.anak_id = ?';
    $params[] = $childId;
}
$sql .= ' ORDER BY l.uploaded_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
