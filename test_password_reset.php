<?php
// Test khusus untuk password reset
header('Content-Type: application/json');

try {
    require_once 'backend/config.php';
    
    echo "=== TEST PASSWORD RESET ===\n\n";
    
    // Test 1: Database connection
    echo "1. Testing database connection...\n";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch();
    echo "✓ Users table: " . $result['count'] . " records\n";
    
    // Test 2: Password_resets table
    echo "2. Testing password_resets table...\n";
    try {
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM password_resets');
        $result = $stmt->fetch();
        echo "✓ Password_resets table: " . $result['count'] . " records\n";
    } catch (Exception $e) {
        echo "✗ Password_resets table error: " . $e->getMessage() . "\n";
    }
    
    // Test 3: Test user data
    echo "3. Testing user data...\n";
    $stmt = $pdo->prepare('SELECT username, email, nama_lengkap FROM users WHERE username = ?');
    $stmt->execute(['T001']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Test user T001 found:\n";
        echo "  - Username: " . $user['username'] . "\n";
        echo "  - Email: " . $user['email'] . "\n";
        echo "  - Name: " . $user['nama_lengkap'] . "\n";
    } else {
        echo "✗ Test user T001 not found\n";
    }
    
    // Test 4: Test password reset API
    echo "4. Testing password reset API...\n";
    
    // Simulate POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $testData = ['username' => 'T001'];
    
    // Capture output
    ob_start();
    
    // Include password_reset.php
    include 'backend/password_reset.php';
    
    $output = ob_get_clean();
    
    echo "API Response: " . $output . "\n";
    
    // Test 5: Check if token was created
    echo "5. Checking if token was created...\n";
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM password_resets');
    $result = $stmt->fetch();
    echo "Password_resets records after test: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== END TEST ===\n";
?>
