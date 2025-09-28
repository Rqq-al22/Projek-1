<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = require_login();

if ($method === 'POST') {
    require_role_staff($user);
    $body = read_json_body();
    $childId = (int)($body['child_id'] ?? 0);
    $status = (string)($body['status'] ?? 'hadir'); // hadir | izin | sakit | alpa
    $note = trim((string)($body['note'] ?? ''));
    if ($childId <= 0) {
        json_response(400, ['ok' => false, 'error' => 'MISSING_CHILD_ID']);
    }
    $pdo->prepare('INSERT INTO absensi (anak_id, terapis_id, tanggal, status, catatan) VALUES (?, ?, CURDATE(), ?, ?)')
        ->execute([$childId, (int)$user['id'], $status, $note]);

    // cek batas izin > 4 pada bulan berjalan
    if ($status === 'izin') {
        $q = $pdo->prepare("SELECT COUNT(*) AS cnt FROM absensi WHERE anak_id = ? AND status = 'izin' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())");
        $q->execute([$childId]);
        $cnt = (int)($q->fetch()['cnt'] ?? 0);
        if ($cnt > 4) {
            $info = $pdo->prepare('SELECT u.username FROM anak a JOIN users u ON u.user_id = a.orangtua_id WHERE a.anak_id = ? LIMIT 1');
            $info->execute([$childId]);
            $row = $info->fetch();
            if ($row) {
                send_email_stub((string)$row['username'], 'Batas Izin Terlampaui', 'Izin bulan ini melebihi 4x.');
                send_whatsapp_stub((string)$row['username'], 'Izin bulan ini melebihi 4x.');
            }
        }
    }

    json_response(200, ['ok' => true]);
}

if ($method === 'GET') {
    if (is_parent_role($user)) {
        $childId = get_child_id_for_parent($pdo, (int)$user['id']);
        if (!$childId) {
            json_response(200, ['ok' => true, 'items' => [], 'summary' => ['total' => 20, 'hadir' => 0, 'izin' => 0, 'tidak_hadir' => 0]]);
        }
        
        // Get attendance history
        $stmt = $pdo->prepare('SELECT absensi_id as id, status, catatan as note, tanggal as created_at FROM absensi WHERE anak_id = ? ORDER BY tanggal DESC LIMIT 200');
        $stmt->execute([$childId]);
        $items = $stmt->fetchAll();
        
        // Calculate summary statistics
        $summaryStmt = $pdo->prepare('
            SELECT 
                SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = "sakit" OR status = "alpa" THEN 1 ELSE 0 END) as tidak_hadir
            FROM absensi 
            WHERE anak_id = ?
        ');
        $summaryStmt->execute([$childId]);
        $summary = $summaryStmt->fetch();
        
        // Set total kehadiran maksimal untuk semua program pembelajaran
        $summary['total'] = 20;
        
        json_response(200, ['ok' => true, 'items' => $items, 'summary' => $summary]);
    } else {
        // staff: bisa filter by child_id
        $childId = (int)($_GET['child_id'] ?? 0);
        $sql = 'SELECT a.absensi_id as id, a.anak_id as child_id, a.status, a.catatan as note, a.tanggal as created_at, an.nama_anak as child_name FROM absensi a JOIN anak an ON an.anak_id = a.anak_id';
        $params = [];
        if ($childId > 0) {
            $sql .= ' WHERE a.anak_id = ?';
            $params[] = $childId;
        }
        $sql .= ' ORDER BY a.tanggal DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        // Calculate summary for staff view
        $summary = ['total' => 20, 'hadir' => 0, 'izin' => 0, 'tidak_hadir' => 0];
        if ($childId > 0) {
            $summaryStmt = $pdo->prepare('
                SELECT 
                    SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status = "sakit" OR status = "alpa" THEN 1 ELSE 0 END) as tidak_hadir
                FROM absensi 
                WHERE anak_id = ?
            ');
            $summaryStmt->execute([$childId]);
            $summary = $summaryStmt->fetch();
            
            // Set total kehadiran maksimal untuk semua program pembelajaran
            $summary['total'] = 20;
        }
        
        json_response(200, ['ok' => true, 'items' => $items, 'summary' => $summary]);
    }
}

json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
