<?php
// Email sender dengan multiple options
require_once __DIR__.'/config.php';

class EmailSender {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/email_log.txt';
    }
    
    public function send(string $toEmail, string $subject, string $message): bool {
        $this->log("Attempting to send email to: {$toEmail}");
        
        // Method 1: Try Laragon sendmail
        if ($this->tryLaragonSendmail($toEmail, $subject, $message)) {
            $this->log("SUCCESS: Email sent via Laragon sendmail");
            return true;
        }
        
        // Method 2: Try basic mail() function
        if ($this->tryBasicMail($toEmail, $subject, $message)) {
            $this->log("SUCCESS: Email sent via basic mail()");
            return true;
        }
        
        // Method 3: Save to file as fallback
        $this->saveToFile($toEmail, $subject, $message);
        $this->log("FALLBACK: Email saved to file");
        
        return false;
    }
    
    private function tryLaragonSendmail(string $toEmail, string $subject, string $message): bool {
        $this->log("Trying Laragon sendmail...");
        
        // Set sendmail path
        ini_set('sendmail_path', 'C:\\laragon\\bin\\sendmail\\sendmail.exe -t');
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: Labirin Children Center <noreply@labirin.com>',
            'Reply-To: noreply@labirin.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return @mail($toEmail, $subject, $message, implode("\r\n", $headers));
    }
    
    private function tryBasicMail(string $toEmail, string $subject, string $message): bool {
        $this->log("Trying basic mail() function...");
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: Labirin Children Center <noreply@labirin.com>',
            'Reply-To: noreply@labirin.com'
        ];
        
        return @mail($toEmail, $subject, $message, implode("\r\n", $headers));
    }
    
    private function saveToFile(string $toEmail, string $subject, string $message): void {
        $filename = __DIR__ . '/emails/' . date('Y-m-d_H-i-s') . '_' . str_replace('@', '_at_', $toEmail) . '.html';
        
        // Buat folder emails jika belum ada
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
    $sender = new EmailSender();
    $result = $sender->send('test@example.com', 'Test Email', '<h1>Test Email dari Labirin</h1><p>Ini adalah test email.</p>');
    echo $result ? 'Email berhasil dikirim' : 'Email gagal dikirim, cek log dan folder emails/';
}
?>
