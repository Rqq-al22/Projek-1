<?php
require_once 'backend/config.php';

echo "=== CHECK LOGIN SYSTEM ===\n\n";

// 1. Cek koneksi database
echo "1. Testing database connection...\n";
try {
    $stmt = $pdo->query('SELECT 1');
    echo "✓ Database connection OK\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit;
}

// 2. Cek apakah ada data user
echo "2. Checking existing users...\n";
try {
    $stmt = $pdo->query('SELECT user_id, username, role, nama_lengkap FROM users LIMIT 5');
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "✗ No users found in database\n";
        echo "Creating sample users...\n\n";
        
        // Buat user sample
        $sampleUsers = [
            [
                'username' => 'T001',
                'role' => 'terapis',
                'nama_lengkap' => 'Budi Terapis',
                'password' => 'password_terapis'
            ],
            [
                'username' => 'O001',
                'role' => 'orangtua',
                'nama_lengkap' => 'Ibu Sari',
                'password' => 'password_ortu'
            ]
        ];
        
        foreach ($sampleUsers as $user) {
            $passwordHash = password_hash($user['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, role, nama_lengkap, password) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user['username'], $user['role'], $user['nama_lengkap'], $passwordHash]);
            echo "✓ Created user: {$user['nama_lengkap']} ({$user['username']}) - {$user['role']}\n";
        }
        echo "\n";
    } else {
        echo "✓ Found " . count($users) . " users:\n";
        foreach ($users as $user) {
            echo "  - ID: {$user['user_id']}, Username: {$user['username']}, Name: {$user['nama_lengkap']}, Role: {$user['role']}\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking users: " . $e->getMessage() . "\n\n";
}

// 3. Test login function
echo "3. Testing login function...\n";
try {
    // Test dengan user yang ada
    $stmt = $pdo->query('SELECT username, nama_lengkap FROM users LIMIT 1');
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        echo "Testing login with: {$testUser['username']} / {$testUser['nama_lengkap']}\n";
        echo "Password: password_terapis or password_ortu\n";
        echo "✓ Login test data ready\n";
    } else {
        echo "✗ No users available for testing\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing login: " . $e->getMessage() . "\n";
}

echo "\n=== LOGIN CREDENTIALS ===\n";
echo "Try logging in with:\n";
echo "• Username: T001 (terapis) or O001 (orangtua)\n";
echo "• Password: password_terapis or password_ortu\n";
echo "\n=== END ===\n";
?>
