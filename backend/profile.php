<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$user = require_login();

// Kembalikan profil untuk dua peran.
if (is_parent_role($user)) {
    // Ambil data anak milik orang tua
    $childId = get_child_id_for_parent($pdo, (int)$user['id']);
    $child = null;
    $therapists = [];
    $stats = ['jumlah_sesi'=>0];
    if ($childId) {
        $stmt = $pdo->prepare('SELECT a.anak_id AS id, a.nama_anak AS name, a.tanggal_lahir, a.keterangan FROM anak a WHERE a.anak_id = ? LIMIT 1');
        $stmt->execute([$childId]);
        $child = $stmt->fetch() ?: null;

        // Hitung jumlah sesi dari jadwal anak (total baris jadwal)
        $q = $pdo->prepare('SELECT COUNT(*) AS cnt FROM jadwal WHERE anak_id = ?');
        $q->execute([$childId]);
        $stats['jumlah_sesi'] = (int)($q->fetch()['cnt'] ?? 0);

        // Terapis yang menangani anak ini (dari relasi eksplisit atau dari jadwal)
        $t = $pdo->prepare('SELECT DISTINCT u.user_id AS id, u.nama_lengkap, u.username, u.email
            FROM parent_therapist pt JOIN users u ON u.user_id = pt.terapis_id WHERE pt.orangtua_id = ?');
        $t->execute([(int)$user['id']]);
        $therapists = $t->fetchAll();
        if (!$therapists) {
            $t2 = $pdo->prepare('SELECT DISTINCT u.user_id AS id, u.nama_lengkap, u.username, u.email
                FROM jadwal j JOIN users u ON u.user_id = j.terapis_id WHERE j.anak_id = ?');
            $t2->execute([$childId]);
            $therapists = $t2->fetchAll();
        }
    }

    json_response(200, [
        'ok' => true,
        'user' => [
            'id' => (int)$user['id'],
            'nama_lengkap' => $user['nama_lengkap'] ?? $user['username'] ?? '',
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'orangtua',
        ],
        'child' => $child,
        'therapists' => $therapists,
        'stats' => $stats,
    ]);
}

// Terapis: profil sederhana + jumlah anak yang ditangani
if (is_staff_role($user)) {
    $q = $pdo->prepare('SELECT COUNT(DISTINCT anak_id) AS cnt FROM jadwal WHERE terapis_id = ?');
    $q->execute([(int)$user['id']]);
    $cnt = (int)($q->fetch()['cnt'] ?? 0);
    json_response(200, [
        'ok' => true,
        'user' => [
            'id' => (int)$user['id'],
            'nama_lengkap' => $user['nama_lengkap'] ?? $user['username'] ?? '',
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'terapis',
        ],
        'handled_children' => $cnt,
    ]);
}

json_response(405, ['ok'=>false,'error'=>'METHOD_NOT_ALLOWED']);


