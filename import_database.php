<?php
// Script untuk import database yang lengkap
require_once 'backend/config.php';

echo "=== IMPORT DATABASE ===\n\n";

try {
    // Baca file SQL
    $sqlFile = __DIR__ . '/database/projek1_db.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("File SQL tidak ditemukan: " . $sqlFile);
    }
    
    echo "✓ File SQL ditemukan: " . $sqlFile . "\n";
    
    $sqlContent = file_get_contents($sqlFile);
    
    if (empty($sqlContent)) {
        throw new Exception("File SQL kosong");
    }
    
    echo "✓ File SQL berhasil dibaca (" . strlen($sqlContent) . " bytes)\n";
    
    // Split SQL statements
    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));
    
    echo "✓ Ditemukan " . count($statements) . " statement SQL\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Tampilkan info statement yang berhasil
            if (strpos($statement, 'CREATE TABLE') === 0) {
                preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✓ Tabel dibuat: " . $tableName . "\n";
            } elseif (strpos($statement, 'CREATE DATABASE') === 0) {
                echo "✓ Database dibuat\n";
            } elseif (strpos($statement, 'INSERT INTO') === 0) {
                preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches);
                $tableName = $matches[1] ?? 'unknown';
                echo "✓ Data dimasukkan ke: " . $tableName . "\n";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "✗ Error pada statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
            echo "   Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\n=== HASIL IMPORT ===\n";
    echo "✓ Berhasil: " . $successCount . " statement\n";
    echo "✗ Error: " . $errorCount . " statement\n";
    
    // Verifikasi tabel yang dibuat
    echo "\n=== VERIFIKASI TABEL ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    $expectedTables = ['users', 'password_resets', 'anak', 'paket_belajar', 'absensi', 'jadwal', 'laporan'];
    
    foreach ($expectedTables as $expectedTable) {
        $found = false;
        foreach ($tables as $table) {
            if ($table[0] === $expectedTable) {
                $found = true;
                break;
            }
        }
        
        if ($found) {
            echo "✓ " . $expectedTable . "\n";
        } else {
            echo "✗ " . $expectedTable . " (TIDAK DITEMUKAN)\n";
        }
    }
    
    // Test data users
    echo "\n=== VERIFIKASI DATA USERS ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Users: " . $result['count'] . " records\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT username, email, role FROM users LIMIT 3");
        $users = $stmt->fetchAll();
        
        foreach ($users as $user) {
            echo "  - " . $user['username'] . " (" . $user['role'] . ") - " . $user['email'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== END ===\n";
?>
