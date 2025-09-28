<?php
// Debug file untuk melihat error di password_reset.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG PASSWORD RESET ===\n\n";

// Test koneksi database
try {
    require_once 'backend/config.php';
    echo "✓ Config loaded successfully\n";
    
    // Test query database
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch();
    echo "✓ Database connection OK - Users count: " . $result['count'] . "\n";
    
    // Test password_resets table
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM password_resets');
    $result = $stmt->fetch();
    echo "✓ Password_resets table OK - Records count: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== TEST JSON BODY ===\n";
$raw = file_get_contents('php://input');
echo "Raw input: " . ($raw ?: 'empty') . "\n";

$data = json_decode($raw ?: '[]', true);
echo "Decoded data: " . print_r($data, true) . "\n";

echo "\n=== TEST EMAIL FUNCTION ===\n";
try {
    $testResult = send_email_simple('test@example.com', 'Test', 'Test message');
    echo "Email function result: " . ($testResult ? 'true' : 'false') . "\n";
} catch (Exception $e) {
    echo "Email function error: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>
