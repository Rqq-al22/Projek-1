<?php
require_once 'backend/config.php';

echo "=== TEST EMAIL SYSTEM ===\n\n";

// Test konfigurasi email
echo "1. Testing email configuration...\n";
echo "SMTP_FROM: " . ($SMTP_FROM ?? 'not set') . "\n";
echo "APP_URL: " . ($APP_URL ?? 'not set') . "\n\n";

// Test fungsi send_email_simple
echo "2. Testing send_email_simple function...\n";

$testEmail = 'test@example.com';
$testSubject = 'Test Email dari Labirin';
$testMessage = '
<html>
<body>
    <h2>Test Email</h2>
    <p>Ini adalah email test dari sistem Labirin Children Center.</p>
    <p>Waktu: ' . date('Y-m-d H:i:s') . '</p>
</body>
</html>';

$result = send_email_simple($testEmail, $testSubject, $testMessage);

if ($result) {
    echo "✓ Email berhasil dikirim ke: {$testEmail}\n";
} else {
    echo "✗ Email gagal dikirim ke: {$testEmail}\n";
    echo "Cek file email_log.txt untuk detail error\n";
}

echo "\n3. Checking email log...\n";
$logFile = __DIR__ . '/backend/email_log.txt';
if (file_exists($logFile)) {
    echo "✓ Email log file exists: {$logFile}\n";
    $logContent = file_get_contents($logFile);
    if (!empty($logContent)) {
        echo "Recent log entries:\n";
        echo $logContent;
    } else {
        echo "Log file is empty\n";
    }
} else {
    echo "✗ Email log file not found\n";
}

echo "\n=== LARAGON EMAIL CONFIGURATION ===\n";
echo "Untuk mengaktifkan email di Laragon:\n";
echo "1. Buka Laragon Control Panel\n";
echo "2. Klik 'Menu' > 'Apache' > 'php.ini'\n";
echo "3. Cari [mail function] dan set:\n";
echo "   sendmail_path = \"C:\\laragon\\bin\\sendmail\\sendmail.exe -t\"\n";
echo "   SMTP = localhost\n";
echo "   smtp_port = 25\n";
echo "4. Restart Apache\n\n";

echo "=== TEST RESET PASSWORD ===\n";
echo "Untuk test reset password:\n";
echo "1. Akses: http://localhost/Projek/frontend/login.html\n";
echo "2. Klik 'Lupa password?'\n";
echo "3. Masukkan username: T001\n";
echo "4. Cek email: rezkialya0909@gmail.com\n\n";

echo "=== END ===\n";
?>
