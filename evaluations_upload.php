<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$user = require_login();
require_role_staff($user);

ensure_upload_dir(__DIR__ . '/uploads');

$childId = (int)($_POST['child_id'] ?? 0);
$caption = trim((string)($_POST['caption'] ?? ''));
$meeting_date = trim((string)($_POST['meeting_date'] ?? date('Y-m-d')));

if ($childId <= 0 || !isset($_FILES['file'])) {
    json_response(400, ['ok' => false, 'error' => 'MISSING_FIELDS']);
}

$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) {
    json_response(400, ['ok' => false, 'error' => 'UPLOAD_ERROR']);
}

$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    json_response(400, ['ok' => false, 'error' => 'ONLY_PDF_ALLOWED']);
}

$basename = 'eval_' . $childId . '_' . time() . '.pdf';
$targetPath = __DIR__ . '/uploads/' . $basename;
move_uploaded_file($f['tmp_name'], $targetPath);

$pdo->prepare('INSERT INTO evaluations (child_id, staff_user_id, caption, file_path, meeting_date) VALUES (?, ?, ?, ?, ?)')
    ->execute([$childId, (int)$user['id'], $caption, $basename, $meeting_date]);

json_response(200, ['ok' => true, 'file' => $basename]);
