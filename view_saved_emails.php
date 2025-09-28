<?php
// View saved emails
echo "=== SAVED EMAILS ===\n\n";

$emailsDir = __DIR__ . '/backend/emails';

if (is_dir($emailsDir)) {
    $files = scandir($emailsDir);
    $emailFiles = array_filter($files, function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'html';
    });
    
    if (empty($emailFiles)) {
        echo "📭 Belum ada email yang tersimpan\n";
    } else {
        echo "📧 Email yang tersimpan (" . count($emailFiles) . " file):\n";
        echo "=====================================\n";
        
        // Sort by date (newest first)
        usort($emailFiles, function($a, $b) {
            return filemtime($emailsDir . '/' . $b) - filemtime($emailsDir . '/' . $a);
        });
        
        foreach ($emailFiles as $file) {
            $filePath = $emailsDir . '/' . $file;
            $fileTime = date('Y-m-d H:i:s', filemtime($filePath));
            $fileSize = filesize($filePath);
            
            echo "📄 {$file}\n";
            echo "   Waktu: {$fileTime}\n";
            echo "   Ukuran: {$fileSize} bytes\n";
            echo "   Link: <a href='backend/emails/{$file}' target='_blank'>Buka Email</a>\n";
            echo "---\n";
        }
    }
} else {
    echo "❌ Folder emails belum dibuat\n";
    echo "💡 Folder akan dibuat otomatis saat ada email yang dikirim\n";
}

echo "\n=== EMAIL LOG ===\n";
$logFile = __DIR__ . '/backend/email_log.txt';

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    
    if (!empty($logContent)) {
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -10); // 10 baris terakhir
        
        echo "📋 Log terbaru:\n";
        foreach ($recentLines as $line) {
            if (!empty(trim($line))) {
                echo "  " . $line . "\n";
            }
        }
    } else {
        echo "📭 Log kosong\n";
    }
} else {
    echo "❌ Log file tidak ditemukan\n";
}

echo "\n=== TEST EMAIL ===\n";
echo "Untuk test email: <a href='backend/email_sender.php?test=1'>Test Email Sender</a>\n";

echo "\n=== END ===\n";
?>
