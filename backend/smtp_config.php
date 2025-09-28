<?php
// Konfigurasi SMTP untuk email yang benar-benar terkirim
require_once __DIR__.'/config.php';

// KONFIGURASI SMTP - GANTI DENGAN DATA ANDA
$SMTP_CONFIG = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-email@gmail.com', // Ganti dengan email Gmail Anda
    'password' => 'your-app-password', // Ganti dengan App Password Gmail
    'from_name' => 'Labirin Children Center',
    'from_email' => 'your-email@gmail.com'
];

function send_email_smtp(string $toEmail, string $subject, string $message): bool {
    global $SMTP_CONFIG;
    
    $logFile = __DIR__ . '/email_log.txt';
    
    // Log attempt
    $logEntry = date('Y-m-d H:i:s') . " - SMTP ATTEMPT\n";
    $logEntry .= "TO: {$toEmail}\n";
    $logEntry .= "SUBJECT: {$subject}\n";
    $logEntry .= "SMTP: {$SMTP_CONFIG['host']}:{$SMTP_CONFIG['port']}\n";
    $logEntry .= "---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Method 1: Try using mail() with SMTP settings
    if (send_email_via_mail($toEmail, $subject, $message)) {
        $logEntry = date('Y-m-d H:i:s') . " - SUCCESS: Email sent via mail() function\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        return true;
    }
    
    // Method 2: Try using cURL to external SMTP service
    if (send_email_via_curl($toEmail, $subject, $message)) {
        $logEntry = date('Y-m-d H:i:s') . " - SUCCESS: Email sent via cURL\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        return true;
    }
    
    // Method 3: Fallback to file
    save_email_to_file($toEmail, $subject, $message);
    $logEntry = date('Y-m-d H:i:s') . " - FALLBACK: Email saved to file\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    return false;
}

function send_email_via_mail(string $toEmail, string $subject, string $message): bool {
    global $SMTP_CONFIG;
    
    // Set SMTP settings
    ini_set('SMTP', $SMTP_CONFIG['host']);
    ini_set('smtp_port', $SMTP_CONFIG['port']);
    ini_set('sendmail_from', $SMTP_CONFIG['from_email']);
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $SMTP_CONFIG['from_name'] . ' <' . $SMTP_CONFIG['from_email'] . '>',
        'Reply-To: ' . $SMTP_CONFIG['from_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return @mail($toEmail, $subject, $message, implode("\r\n", $headers));
}

function send_email_via_curl(string $toEmail, string $subject, string $message): bool {
    global $SMTP_CONFIG;
    
    // Use external email service like EmailJS or similar
    $data = [
        'to' => $toEmail,
        'subject' => $subject,
        'message' => $message,
        'from' => $SMTP_CONFIG['from_email'],
        'from_name' => $SMTP_CONFIG['from_name']
    ];
    
    // This is a placeholder - you would need to implement actual cURL to email service
    // For now, return false to use fallback
    return false;
}

function save_email_to_file(string $toEmail, string $subject, string $message): void {
    $emailsDir = __DIR__ . '/emails';
    if (!is_dir($emailsDir)) {
        mkdir($emailsDir, 0755, true);
    }
    
    $filename = $emailsDir . '/' . date('Y-m-d_H-i-s') . '_' . str_replace('@', '_at_', $toEmail) . '.html';
    
    $content = "<!DOCTYPE html>\n";
    $content .= "<html><head><title>{$subject}</title></head>\n";
    $content .= "<body>\n";
    $content .= "<h2>📧 Email untuk: {$toEmail}</h2>\n";
    $content .= "<h3>Subject: {$subject}</h3>\n";
    $content .= "<hr>\n";
    $content .= $message . "\n";
    $content .= "<hr>\n";
    $content .= "<p><small>Dikirim pada: " . date('Y-m-d H:i:s') . "</small></p>\n";
    $content .= "<p><strong>💡 Untuk mengirim email ke inbox:</strong></p>\n";
    $content .= "<ol>\n";
    $content .= "<li>Konfigurasi SMTP Gmail di file ini</li>\n";
    $content .= "<li>Buat App Password di Gmail</li>\n";
    $content .= "<li>Update konfigurasi SMTP_CONFIG</li>\n";
    $content .= "</ol>\n";
    $content .= "</body></html>";
    
    file_put_contents($filename, $content);
}

// Test function
if (isset($_GET['test'])) {
    $result = send_email_smtp('test@example.com', 'Test SMTP Config', '<h1>Test SMTP Config dari Labirin</h1>');
    echo $result ? 'SMTP berhasil dikirim' : 'SMTP gagal, cek log dan folder emails/';
}
?>