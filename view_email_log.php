<?php
// View email log untuk debugging
echo "=== EMAIL LOG ===\n\n";

$logFile = __DIR__ . '/backend/email_log.txt';

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    
    if (!empty($logContent)) {
        echo "📧 Email Log:\n";
        echo "=============\n";
        echo $logContent;
    } else {
        echo "📭 Log file kosong - belum ada email yang dikirim\n";
    }
} else {
    echo "❌ Log file tidak ditemukan: {$logFile}\n";
}

echo "\n=== PHP MAIL CONFIGURATION ===\n";

// Cek konfigurasi PHP mail
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "mail.add_x_header: " . ini_get('mail.add_x_header') . "\n";

echo "\n=== LARAGON SENDMAIL CHECK ===\n";

$sendmailPath = "C:\\laragon\\bin\\sendmail\\sendmail.exe";
if (file_exists($sendmailPath)) {
    echo "✅ Sendmail ditemukan: {$sendmailPath}\n";
} else {
    echo "❌ Sendmail tidak ditemukan: {$sendmailPath}\n";
    echo "💡 Pastikan Laragon terinstall dengan lengkap\n";
}

echo "\n=== TEST EMAIL FUNCTION ===\n";

try {
    require_once 'backend/config.php';
    
    // Test email function
    $testResult = send_email_simple('test@example.com', 'Test Email', '<h1>Test Email dari Labirin</h1>');
    
    if ($testResult) {
        echo "✅ Email function berhasil\n";
    } else {
        echo "❌ Email function gagal\n";
    }
    
    // Cek log setelah test
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -5); // 5 baris terakhir
        
        echo "\n📋 Log terbaru:\n";
        foreach ($lastLines as $line) {
            if (!empty(trim($line))) {
                echo "  " . $line . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== SOLUSI EMAIL TIDAK TERKIRIM ===\n";
echo "1. Pastikan Laragon Apache sudah restart setelah konfigurasi php.ini\n";
echo "2. Cek file email_log.txt untuk detail error\n";
echo "3. Jika masih gagal, gunakan SMTP eksternal (Gmail, dll)\n";
echo "4. Untuk testing, email akan tersimpan di log file\n";

echo "\n=== END ===\n";
?>
