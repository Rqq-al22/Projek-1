<?php
// Script untuk membuat tabel password_resets
require_once 'backend/config.php';

echo "=== CREATE PASSWORD_RESETS TABLE ===\n\n";

try {
    // Cek apakah tabel sudah ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✓ Tabel password_resets sudah ada\n";
        
        // Cek struktur tabel
        $stmt = $pdo->query("DESCRIBE password_resets");
        $columns = $stmt->fetchAll();
        
        echo "Struktur tabel:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
    } else {
        echo "✗ Tabel password_resets belum ada, membuat tabel...\n";
        
        // Buat tabel password_resets
        $sql = "CREATE TABLE password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expired_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "✓ Tabel password_resets berhasil dibuat\n";
    }
    
    // Test insert data
    echo "\nTesting insert data...\n";
    $testEmail = 'test@example.com';
    $testToken = bin2hex(random_bytes(16));
    $testExpired = date('Y-m-d H:i:s', time() + 3600);
    
    $stmt = $pdo->prepare('INSERT INTO password_resets (email, token, expired_at) VALUES (?, ?, ?)');
    $stmt->execute([$testEmail, $testToken, $testExpired]);
    
    echo "✓ Test insert berhasil\n";
    
    // Test select data
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE email = ?');
    $stmt->execute([$testEmail]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ Test select berhasil\n";
        echo "  - ID: " . $result['id'] . "\n";
        echo "  - Email: " . $result['email'] . "\n";
        echo "  - Token: " . substr($result['token'], 0, 10) . "...\n";
        echo "  - Expired: " . $result['expired_at'] . "\n";
    }
    
    // Hapus data test
    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
    $stmt->execute([$testEmail]);
    echo "✓ Data test berhasil dihapus\n";
    
    echo "\n=== SEMUA TABEL DI DATABASE ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    foreach ($tables as $table) {
        echo "✓ " . $table[0] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== END ===\n";
?>
