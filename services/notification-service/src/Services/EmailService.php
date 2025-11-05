<?php

namespace App\Services;

class EmailService
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromAddress;
    private $fromName;
    
    public function __construct()
    {
        $this->host = $_ENV['MAIL_HOST'] ?? 'mailpit';
        $this->port = $_ENV['MAIL_PORT'] ?? 1025;
        $this->username = $_ENV['MAIL_USERNAME'] ?? null;
        $this->password = $_ENV['MAIL_PASSWORD'] ?? null;
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@evmwarranty.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'EVM Warranty System';
    }
    
    /**
     * Send email notification
     */
    public function send($to, $subject, $message, $template = null, $data = [])
    {
        try {
            // Use PHPMailer or simple mail() function
            $headers = [
                'From: ' . $this->fromName . ' <' . $this->fromAddress . '>',
                'Reply-To: ' . $this->fromAddress,
                'Content-Type: text/html; charset=UTF-8',
                'X-Mailer: EVM Notification System'
            ];
            
            // If template is provided, render it
            if ($template) {
                $message = $this->renderTemplate($template, $data, $message);
            }
            
            // For development, log email instead of sending
            if ($_ENV['APP_ENV'] === 'local') {
                $this->logEmail($to, $subject, $message);
                return [
                    'success' => true,
                    'message_id' => 'dev_' . uniqid(),
                    'status' => 'sent'
                ];
            }
            
            // Send actual email using SMTP
            $result = $this->sendSMTP($to, $subject, $message, $headers);
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send SMTP email
     */
    private function sendSMTP($to, $subject, $message, $headers)
    {
        // Simple SMTP implementation
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new \Exception("Failed to connect to SMTP server: $errstr ($errno)");
        }
        
        // SMTP conversation
        $this->smtpCommand($socket, null, "220"); // Server greeting
        $this->smtpCommand($socket, "EHLO " . $_SERVER['HTTP_HOST'] ?? 'localhost', "250");
        
        if ($this->username && $this->password) {
            $this->smtpCommand($socket, "AUTH LOGIN", "334");
            $this->smtpCommand($socket, base64_encode($this->username), "334");
            $this->smtpCommand($socket, base64_encode($this->password), "235");
        }
        
        $this->smtpCommand($socket, "MAIL FROM: <{$this->fromAddress}>", "250");
        $this->smtpCommand($socket, "RCPT TO: <{$to}>", "250");
        $this->smtpCommand($socket, "DATA", "354");
        
        // Send email headers and body
        $email = implode("\r\n", $headers) . "\r\n";
        $email .= "To: {$to}\r\n";
        $email .= "Subject: {$subject}\r\n\r\n";
        $email .= $message . "\r\n.\r\n";
        
        fwrite($socket, $email);
        $response = fgets($socket, 256);
        
        $this->smtpCommand($socket, "QUIT", "221");
        fclose($socket);
        
        // Parse response for message ID
        $messageId = 'smtp_' . uniqid();
        
        return [
            'success' => true,
            'message_id' => $messageId,
            'status' => 'sent'
        ];
    }
    
    /**
     * Execute SMTP command
     */
    private function smtpCommand($socket, $command, $expectedCode)
    {
        if ($command) {
            fwrite($socket, $command . "\r\n");
        }
        
        $response = fgets($socket, 256);
        $code = substr($response, 0, 3);
        
        if ($code !== $expectedCode) {
            throw new \Exception("SMTP Error: Expected {$expectedCode}, got {$code} - {$response}");
        }
        
        return $response;
    }
    
    /**
     * Render email template
     */
    private function renderTemplate($template, $data, $defaultMessage)
    {
        $templates = [
            'notification' => '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                        .header { background: #1f2937; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                        .content { padding: 20px; }
                        .footer { background: #f8f9fa; padding: 15px; text-align: center; color: #666; font-size: 12px; }
                        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>🚗 EVM Warranty System</h1>
                        </div>
                        <div class="content">
                            <h2>' . ($data['title'] ?? 'Thông báo') . '</h2>
                            <p>' . ($data['message'] ?? $defaultMessage) . '</p>
                            ' . (isset($data['action_url']) ? '<p><a href="' . $data['action_url'] . '" class="button">Xem chi tiết</a></p>' : '') . '
                        </div>
                        <div class="footer">
                            <p>© 2024 EVM Warranty Management System. Đây là email tự động, vui lòng không trả lời.</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'appointment' => '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                        .header { background: #059669; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                        .appointment-details { background: #f0fdf4; padding: 15px; border-radius: 6px; margin: 15px 0; }
                        .detail-row { display: flex; justify-content: space-between; margin: 8px 0; }
                        .label { font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>📅 Lịch Hẹn EVM</h1>
                        </div>
                        <div class="content">
                            <h2>' . ($data['title'] ?? 'Xác nhận lịch hẹn') . '</h2>
                            <p>' . ($data['message'] ?? $defaultMessage) . '</p>
                            <div class="appointment-details">
                                <div class="detail-row">
                                    <span class="label">Ngày hẹn:</span>
                                    <span>' . ($data['appointment_date'] ?? 'N/A') . '</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Giờ hẹn:</span>
                                    <span>' . ($data['appointment_time'] ?? 'N/A') . '</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Loại dịch vụ:</span>
                                    <span>' . ($data['service_type'] ?? 'N/A') . '</span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Trung tâm dịch vụ:</span>
                                    <span>' . ($data['service_center'] ?? 'N/A') . '</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ',
            'campaign' => '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                        .header { background: #7c3aed; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                        .campaign-content { padding: 20px; }
                        .cta-button { text-align: center; margin: 20px 0; }
                        .button { display: inline-block; padding: 15px 30px; background: #7c3aed; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h1>📢 ' . ($data['campaign_name'] ?? 'Thông báo đặc biệt') . '</h1>
                        </div>
                        <div class="campaign-content">
                            <h2>' . ($data['title'] ?? 'Thông báo') . '</h2>
                            <p>' . ($data['message'] ?? $defaultMessage) . '</p>
                            ' . (isset($data['cta_text']) && isset($data['cta_url']) ? 
                                '<div class="cta-button">
                                    <a href="' . $data['cta_url'] . '" class="button">' . $data['cta_text'] . '</a>
                                </div>' : '') . '
                        </div>
                    </div>
                </body>
                </html>
            '
        ];
        
        return $templates[$template] ?? $defaultMessage;
    }
    
    /**
     * Log email for development
     */
    private function logEmail($to, $subject, $message)
    {
        $logEntry = [
            'timestamp' => date('c'),
            'to' => $to,
            'subject' => $subject,
            'message' => strip_tags($message)
        ];
        
        error_log("EMAIL LOG: " . json_encode($logEntry, JSON_UNESCAPED_UNICODE));
        
        // Also write to file if needed
        $logFile = __DIR__ . '/../../logs/email.log';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get email templates
     */
    public function getTemplates()
    {
        return [
            'notification' => 'General notification template',
            'appointment' => 'Appointment confirmation template',
            'campaign' => 'Marketing campaign template'
        ];
    }
}