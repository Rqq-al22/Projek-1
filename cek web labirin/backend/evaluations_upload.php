<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$user = require_login();
require_role_staff($user);

ensure_upload_dir(__DIR__ . '/uploads');

$childId = (int)($_POST['child_id'] ?? 0);
$judul = trim((string)($_POST['caption'] ?? ''));
$pertemuan_ke = (int)($_POST['pertemuan_ke'] ?? 1);

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

$basename = 'laporan_' . $childId . '_' . time() . '.pdf';
$targetPath = __DIR__ . '/uploads/' . $basename;
move_uploaded_file($f['tmp_name'], $targetPath);

$pdo->prepare('INSERT INTO laporan (anak_id, terapis_id, pertemuan_ke, judul, file_path) VALUES (?, ?, ?, ?, ?)')
    ->execute([$childId, (int)$user['id'], $pertemuan_ke, $judul, $basename]);

json_response(200, ['ok' => true, 'file' => $basename]);
