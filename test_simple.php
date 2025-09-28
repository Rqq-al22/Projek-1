<?php
// Test sederhana untuk password reset
header('Content-Type: application/json');

try {
    require_once 'backend/config.php';
    
    // Test basic functionality
    $result = [
        'ok' => true,
        'message' => 'Test berhasil',
        'database' => 'Connected',
        'time' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
