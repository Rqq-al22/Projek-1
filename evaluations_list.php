<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$user = require_login();

if (is_parent_role($user)) {
    $childId = get_child_id_for_parent($pdo, (int)$user['id']);
    if (!$childId) {
        json_response(200, ['ok' => true, 'items' => []]);
    }
    $stmt = $pdo->prepare('SELECT id, caption, file_path, meeting_date, created_at FROM evaluations WHERE child_id = ? ORDER BY meeting_date DESC, created_at DESC');
    $stmt->execute([$childId]);
    json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
}

// staff view (optional filter by child_id)
$childId = (int)($_GET['child_id'] ?? 0);
$sql = 'SELECT e.id, e.child_id, c.name as child_name, e.caption, e.file_path, e.meeting_date, e.created_at FROM evaluations e JOIN children c ON c.id = e.child_id';
$params = [];
if ($childId > 0) {
    $sql .= ' WHERE e.child_id = ?';
    $params[] = $childId;
}
$sql .= ' ORDER BY e.meeting_date DESC, e.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
