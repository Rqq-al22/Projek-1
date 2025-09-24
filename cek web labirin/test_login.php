<?php
require_once 'backend/config.php';

// Test login endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $login = trim($input['login'] ?? '');
    $password = $input['password'] ?? '';
    
    echo "Testing login with: '$login' and password: '$password'\n";
    
    if ($login === '' || $password === '') {
        echo "ERROR: Missing credentials\n";
        exit;
    }
    
    $stmt = $pdo->prepare('SELECT user_id, username, password, role, nama_lengkap FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "ERROR: User not found\n";
        exit;
    }
    
    echo "Found user: " . $user['nama_lengkap'] . " (" . $user['username'] . ")\n";
    echo "Stored password hash: " . $user['password'] . "\n";
    
    if (password_matches($password, $user['password'])) {
        echo "SUCCESS: Password matches!\n";
        $_SESSION['user'] = [
            'id' => (int)$user['user_id'],
            'username' => (string)$user['username'],
            'name' => (string)$user['nama_lengkap'],
            'role' => (string)$user['role'],
        ];
        echo "Session set: " . json_encode($_SESSION['user']) . "\n";
    } else {
        echo "ERROR: Password does not match\n";
    }
} else {
    echo "Send POST request with JSON body: {\"login\": \"T001\", \"password\": \"password_terapis\"}\n";
}
?>
