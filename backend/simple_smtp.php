<?php
// SMTP sederhana yang mudah dikonfigurasi
require_once __DIR__.'/config.php';

// KONFIGURASI SMTP - SESUAIKAN DENGAN EMAIL ANDA
$SMTP_SETTINGS = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com', // Ganti dengan email Gmail Anda
    'smtp_password' => 'your-app-password', // Ganti dengan App Password Gmail
    'smtp_secure' => 'tls', // atau 'ssl' untuk port 465
    'from_name' => 'Labirin Children Center',
    'from_email' => 'your-email@gmail.com'
];

function send_email_simple_smtp(string $toEmail, string $subject, string $message): bool {
    global $SMTP_SETTINGS;
    
    $logFile = __DIR__ . '/email_log.txt';
    
    // Log attempt
    $logEntry = date('Y-m-d H:i:s') . " - SIMPLE SMTP ATTEMPT\n";
    $logEntry .= "TO: {$toEmail}\n";
    $logEntry .= "SUBJECT: {$subject}\n";
    $logEntry .= "SMTP: {$SMTP_SETTINGS['smtp_host']}:{$SMTP_SETTINGS['smtp_port']}\n";
    $logEntry .= "USER: {$SMTP_SETTINGS['smtp_username']}\n";
    $logEntry .= "---\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Method 1: Try dengan konfigurasi PHP mail()
    if (configure_and_send_mail($toEmail, $subject, $message)) {
        $logEntry = date('Y-m-d H:i:s') . " - SUCCESS: Email sent via configured mail()\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        return true;
    }
    
    // Method 2: Try dengan socket connection
    if (send_via_socket($toEmail, $subject, $message)) {
        $logEntry = date('Y-m-d H:i:s') . " - SUCCESS: Email sent via socket\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        return true;
    }
    
    // Method 3: Fallback to file
    save_email_file($toEmail, $subject, $message);
    $logEntry = date('Y-m-d H:i:s') . " - FALLBACK: Email saved to file\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    return false;
}

function configure_and_send_mail(string $toEmail, string $subject, string $message): bool {
    global $SMTP_SETTINGS;
    
    // Set SMTP configuration
    ini_set('SMTP', $SMTP_SETTINGS['smtp_host']);
    ini_set('smtp_port', $SMTP_SETTINGS['smtp_port']);
    ini_set('sendmail_from', $SMTP_SETTINGS['from_email']);
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $SMTP_SETTINGS['from_name'] . ' <' . $SMTP_SETTINGS['from_email'] . '>',
        'Reply-To: ' . $SMTP_SETTINGS['from_email'],
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 3'
    ];
    
    return @mail($toEmail, $subject, $message, implode("\r\n", $headers));
}

function send_via_socket(string $toEmail, string $subject, string $message): bool {
    global $SMTP_SETTINGS;
    
    try {
        // Create socket connection
        $socket = fsockopen($SMTP_SETTINGS['smtp_host'], $SMTP_SETTINGS['smtp_port'], $errno, $errstr, 30);
        
        if (!$socket) {
            error_log("Socket connection failed: {$errstr} ({$errno})");
            return false;
        }
        
        // SMTP conversation
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            return false;
        }
        
        // EHLO
        fwrite($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);
        
        // STARTTLS
        fwrite($socket, "STARTTLS\r\n");
        $response = fgets($socket, 512);
        
        if (substr($response, 0, 3) == '220') {
            // Enable TLS
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // EHLO again after TLS
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket, 512);
            
            // AUTH LOGIN
            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 512);
            
            if (substr($response, 0, 3) == '334') {
                // Send username
                fwrite($socket, base64_encode($SMTP_SETTINGS['smtp_username']) . "\r\n");
                $response = fgets($socket, 512);
                
                if (substr($response, 0, 3) == '334') {
                    // Send password
                    fwrite($socket, base64_encode($SMTP_SETTINGS['smtp_password']) . "\r\n");
                    $response = fgets($socket, 512);
                    
                    if (substr($response, 0, 3) == '235') {
                        // Authentication successful, send email
                        fwrite($socket, "MAIL FROM: <{$SMTP_SETTINGS['from_email']}>\r\n");
                        $response = fgets($socket, 512);
                        
                        if (substr($response, 0, 3) == '250') {
                            fwrite($socket, "RCPT TO: <{$toEmail}>\r\n");
                            $response = fgets($socket, 512);
                            
                            if (substr($response, 0, 3) == '250') {
                                fwrite($socket, "DATA\r\n");
                                $response = fgets($socket, 512);
                                
                                if (substr($response, 0, 3) == '354') {
                                    // Send email data
                                    $emailData = "From: {$SMTP_SETTINGS['from_name']} <{$SMTP_SETTINGS['from_email']}>\r\n";
                                    $emailData .= "To: {$toEmail}\r\n";
                                    $emailData .= "Subject: {$subject}\r\n";
                                    $emailData .= "MIME-Version: 1.0\r\n";
                                    $emailData .= "Content-Type: text/html; charset=UTF-8\r\n";
                                    $emailData .= "\r\n";
                                    $emailData .= $message . "\r\n";
                                    $emailData .= ".\r\n";
                                    
                                    fwrite($socket, $emailData);
                                    $response = fgets($socket, 512);
                                    
                                    if (substr($response, 0, 3) == '250') {
                                        fwrite($socket, "QUIT\r\n");
                                        fclose($socket);
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        fclose($socket);
        return false;
        
    } catch (Exception $e) {
        error_log("Socket SMTP error: " . $e->getMessage());
        return false;
    }
}

function save_email_file(string $toEmail, string $subject, string $message): void {
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
    $content .= "<div style='background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
    $content .= "<h4>💡 Cara Mengirim Email ke Inbox:</h4>\n";
    $content .= "<ol>\n";
    $content .= "<li>Buka Gmail → Settings → Security</li>\n";
    $content .= "<li>Aktifkan 2-Factor Authentication</li>\n";
    $content .= "<li>Buat App Password untuk 'Labirin App'</li>\n";
    $content .= "<li>Edit file backend/simple_smtp.php</li>\n";
    $content .= "<li>Ganti your-email@gmail.com dan your-app-password</li>\n";
    $content .= "</ol>\n";
    $content .= "</div>\n";
    $content .= "</body></html>";
    
    file_put_contents($filename, $content);
}

// Test function
if (isset($_GET['test'])) {
    $result = send_email_simple_smtp('test@example.com', 'Test Simple SMTP', '<h1>Test Simple SMTP dari Labirin</h1><p>Ini adalah test email menggunakan konfigurasi sederhana.</p>');
    echo $result ? 'Simple SMTP berhasil dikirim' : 'Simple SMTP gagal, cek log dan folder emails/';
}
?>
