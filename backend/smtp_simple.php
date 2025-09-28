<?php
// SMTP sederhana untuk Laragon
require_once __DIR__.'/config.php';

function send_email_smtp_simple(string $toEmail, string $subject, string $message): bool {
    // Konfigurasi SMTP untuk Laragon
    $smtp_host = 'localhost';
    $smtp_port = 25;
    
    // Headers email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: Labirin Children Center <noreply@labirin.com>',
        'Reply-To: noreply@labirin.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Simpan ke log
    $logFile = __DIR__ . '/email_log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - SMTP ATTEMPT\n";
    $logEntry .= "TO: {$toEmail}\n";
    $logEntry .= "SUBJECT: {$subject}\n";
    $logEntry .= "HOST: {$smtp_host}:{$smtp_port}\n";
    $logEntry .= "---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Coba kirim email
    $ok = @mail($toEmail, $subject, $message, implode("\r\n", $headers));
    
    // Update log
    $status = $ok ? 'SUCCESS' : 'FAILED';
    $logEntry = date('Y-m-d H:i:s') . " - SMTP RESULT: {$status}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    return $ok;
}

// Test function
if (isset($_GET['test'])) {
    $result = send_email_smtp_simple('test@example.com', 'Test SMTP', '<h1>Test SMTP dari Labirin</h1>');
    echo $result ? 'SMTP berhasil' : 'SMTP gagal';
}
?>
