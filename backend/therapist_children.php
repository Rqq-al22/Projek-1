<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$user = require_login();
require_role_staff($user);

// Ambil daftar anak yang ditangani terapis ini dari dua sumber:
// 1) Jadwal (jika sudah ada penjadwalan)
// 2) Relasi parent_therapist (orangtua yang ditangani terapis ini) -> semua anak dari orangtua tsb
$therapistId = (int)$user['id'];

$sql = '(
  SELECT a.anak_id AS id, a.nama_anak AS name, up.nama_lengkap AS parent_name, up.username AS parent_username
  FROM jadwal j
  JOIN anak a ON a.anak_id = j.anak_id
  JOIN users up ON up.user_id = a.orangtua_id
  WHERE j.terapis_id = :tid1
) UNION (
  SELECT a.anak_id AS id, a.nama_anak AS name, up.nama_lengkap AS parent_name, up.username AS parent_username
  FROM parent_therapist pt
  JOIN users up ON up.user_id = pt.orangtua_id
  JOIN anak a ON a.orangtua_id = up.user_id
  WHERE pt.terapis_id = :tid2
)
ORDER BY name';

$stmt = $pdo->prepare($sql);
$stmt->execute([':tid1'=>$therapistId, ':tid2'=>$therapistId]);
$rows = $stmt->fetchAll();

// Deduplicate on id in PHP (karena UNION default sudah menghapus duplikasi, tapi berjaga-jaga)
$seen = [];
$children = [];
foreach ($rows as $r) {
    $id = (int)$r['id'];
    if (isset($seen[$id])) continue;
    $seen[$id] = true;
    $children[] = $r;
}

json_response(200, ['ok'=>true, 'items'=>$children]);


