<?php
// SMTP Gmail untuk email yang benar-benar terkirim
require_once __DIR__.'/config.php';

class GmailSMTP {
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_username = 'your-email@gmail.com'; // Ganti dengan email Gmail Anda
    private $smtp_password = 'your-app-password'; // Ganti dengan App Password Gmail
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/email_log.txt';
    }
    
    public function send(string $toEmail, string $subject, string $message): bool {
        $this->log("Attempting Gmail SMTP to: {$toEmail}");
        
        // Method 1: Try PHPMailer if available
        if ($this->tryPHPMailer($toEmail, $subject, $message)) {
            $this->log("SUCCESS: Email sent via PHPMailer + Gmail SMTP");
            return true;
        }
        
        // Method 2: Try fsockopen SMTP
        if ($this->tryFSockOpen($toEmail, $subject, $message)) {
            $this->log("SUCCESS: Email sent via fsockopen + Gmail SMTP");
            return true;
        }
        
        // Method 3: Fallback to file
        $this->saveToFile($toEmail, $subject, $message);
        $this->log("FALLBACK: Email saved to file");
        
        return false;
    }
    
    private function tryPHPMailer(string $toEmail, string $subject, string $message): bool {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return false;
        }
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            
            // Email content
            $mail->setFrom($this->smtp_username, 'Labirin Children Center');
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            $this->log("PHPMailer error: " . $e->getMessage());
            return false;
        }
    }
    
    private function tryFSockOpen(string $toEmail, string $subject, string $message): bool {
        try {
            $socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
            
            if (!$socket) {
                $this->log("fsockopen failed: {$errstr} ({$errno})");
                return false;
            }
            
            // SMTP conversation
            $this->smtpCommand($socket, "EHLO localhost");
            $this->smtpCommand($socket, "STARTTLS");
            
            // Enable TLS
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            $this->smtpCommand($socket, "EHLO localhost");
            $this->smtpCommand($socket, "AUTH LOGIN");
            $this->smtpCommand($socket, base64_encode($this->smtp_username));
            $this->smtpCommand($socket, base64_encode($this->smtp_password));
            $this->smtpCommand($socket, "MAIL FROM: <{$this->smtp_username}>");
            $this->smtpCommand($socket, "RCPT TO: <{$toEmail}>");
            $this->smtpCommand($socket, "DATA");
            
            // Email headers and body
            $emailData = "From: Labirin Children Center <{$this->smtp_username}>\r\n";
            $emailData .= "To: {$toEmail}\r\n";
            $emailData .= "Subject: {$subject}\r\n";
            $emailData .= "MIME-Version: 1.0\r\n";
            $emailData .= "Content-Type: text/html; charset=utf-8\r\n";
            $emailData .= "\r\n";
            $emailData .= $message . "\r\n";
            $emailData .= ".\r\n";
            
            fwrite($socket, $emailData);
            $this->smtpCommand($socket, "QUIT");
            
            fclose($socket);
            return true;
            
        } catch (Exception $e) {
            $this->log("fsockopen error: " . $e->getMessage());
            return false;
        }
    }
    
    private function smtpCommand($socket, string $command): string {
        fwrite($socket, $command . "\r\n");
        return fgets($socket, 512);
    }
    
    private function saveToFile(string $toEmail, string $subject, string $message): void {
        $filename = __DIR__ . '/emails/' . date('Y-m-d_H-i-s') . '_' . str_replace('@', '_at_', $toEmail) . '.html';
        
        if (!is_dir(__DIR__ . '/emails')) {
            mkdir(__DIR__ . '/emails', 0755, true);
        }
        
        $content = "<!DOCTYPE html>\n";
        $content .= "<html><head><title>{$subject}</title></head>\n";
        $content .= "<body>\n";
        $content .= "<h2>Email untuk: {$toEmail}</h2>\n";
        $content .= "<h3>Subject: {$subject}</h3>\n";
        $content .= "<hr>\n";
        $content .= $message . "\n";
        $content .= "<hr>\n";
        $content .= "<p><small>Dikirim pada: " . date('Y-m-d H:i:s') . "</small></p>\n";
        $content .= "</body></html>";
        
        file_put_contents($filename, $content);
        $this->log("Email saved to file: {$filename}");
    }
    
    private function log(string $message): void {
        $logEntry = date('Y-m-d H:i:s') . " - " . $message . "\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Test function
if (isset($_GET['test'])) {
    $sender = new GmailSMTP();
    $result = $sender->send('test@example.com', 'Test Gmail SMTP', '<h1>Test Gmail SMTP dari Labirin</h1>');
    echo $result ? 'Gmail SMTP berhasil' : 'Gmail SMTP gagal, cek log';
}
?>
