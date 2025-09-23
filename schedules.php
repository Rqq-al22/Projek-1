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
        $stmt = $pdo->prepare('SELECT id, child_id, therapist_user_id, day_of_week, time_start, time_end, room, notes FROM schedules WHERE child_id = ? ORDER BY day_of_week, time_start');
        $stmt->execute([$childId]);
        json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
    } else {
        $stmt = $pdo->query('SELECT s.id, s.child_id, c.name as child_name, s.therapist_user_id, s.day_of_week, s.time_start, s.time_end, s.room, s.notes FROM schedules s JOIN children c ON c.id = s.child_id ORDER BY s.day_of_week, s.time_start');
        json_response(200, ['ok' => true, 'items' => $stmt->fetchAll()]);
    }
}

if ($method === 'POST') {
    require_role_staff($user);
    $body = read_json_body();
    $childId = (int)($body['child_id'] ?? 0);
    $therapistId = (int)($body['therapist_user_id'] ?? (int)$user['id']);
    $dow = (string)($body['day_of_week'] ?? 'Senin');
    $start = (string)($body['time_start'] ?? '09:00');
    $end = (string)($body['time_end'] ?? '10:00');
    $room = trim((string)($body['room'] ?? '')); 
    $notes = trim((string)($body['notes'] ?? ''));
    if ($childId <= 0) {
        json_response(400, ['ok' => false, 'error' => 'MISSING_CHILD_ID']);
    }
    $pdo->prepare('INSERT INTO schedules (child_id, therapist_user_id, day_of_week, time_start, time_end, room, notes) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$childId, $therapistId, $dow, $start, $end, $room, $notes]);
    json_response(200, ['ok' => true]);
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
