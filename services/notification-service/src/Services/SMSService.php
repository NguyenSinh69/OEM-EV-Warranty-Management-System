<?php

namespace App\Services;

class SMSService
{
    private $provider;
    private $apiKey;
    private $apiSecret;
    private $fromNumber;
    
    public function __construct()
    {
        $this->provider = $_ENV['SMS_PROVIDER'] ?? 'mock';
        $this->apiKey = $_ENV['SMS_API_KEY'] ?? null;
        $this->apiSecret = $_ENV['SMS_API_SECRET'] ?? null;
        $this->fromNumber = $_ENV['SMS_FROM_NUMBER'] ?? '+84123456789';
    }
    
    /**
     * Send SMS notification
     */
    public function send($to, $message, $type = 'notification')
    {
        try {
            // Validate phone number
            $cleanPhone = $this->cleanPhoneNumber($to);
            if (!$this->isValidPhoneNumber($cleanPhone)) {
                throw new \Exception("Invalid phone number: {$to}");
            }
            
            // Truncate message if too long
            $message = $this->truncateMessage($message);
            
            switch ($this->provider) {
                case 'twilio':
                    return $this->sendTwilio($cleanPhone, $message);
                case 'vonage':
                    return $this->sendVonage($cleanPhone, $message);
                case 'esms':
                    return $this->sendESMS($cleanPhone, $message);
                case 'mock':
                default:
                    return $this->sendMock($cleanPhone, $message);
            }
            
        } catch (\Exception $e) {
            error_log("SMS send error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send SMS via Twilio
     */
    private function sendTwilio($to, $message)
    {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->apiKey}/Messages.json";
        
        $data = [
            'From' => $this->fromNumber,
            'To' => $to,
            'Body' => $message
        ];
        
        $response = $this->makeHttpRequest($url, $data, [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)
        ]);
        
        if ($response['success']) {
            $result = json_decode($response['body'], true);
            return [
                'success' => true,
                'message_id' => $result['sid'] ?? 'twilio_' . uniqid(),
                'status' => 'sent',
                'provider' => 'twilio'
            ];
        }
        
        throw new \Exception("Twilio API error: " . $response['error']);
    }
    
    /**
     * Send SMS via Vonage (Nexmo)
     */
    private function sendVonage($to, $message)
    {
        $url = "https://rest.nexmo.com/sms/json";
        
        $data = [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'from' => $this->fromNumber,
            'to' => $to,
            'text' => $message
        ];
        
        $response = $this->makeHttpRequest($url, $data);
        
        if ($response['success']) {
            $result = json_decode($response['body'], true);
            $messageStatus = $result['messages'][0] ?? [];
            
            if ($messageStatus['status'] === '0') {
                return [
                    'success' => true,
                    'message_id' => $messageStatus['message-id'],
                    'status' => 'sent',
                    'provider' => 'vonage'
                ];
            }
            
            throw new \Exception("Vonage error: " . $messageStatus['error-text']);
        }
        
        throw new \Exception("Vonage API error: " . $response['error']);
    }
    
    /**
     * Send SMS via eSMS (Vietnam provider)
     */
    private function sendESMS($to, $message)
    {
        $url = "http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/";
        
        $data = [
            'ApiKey' => $this->apiKey,
            'SecretKey' => $this->apiSecret,
            'Phone' => $to,
            'Content' => $message,
            'SmsType' => 2, // Brandname SMS
            'Brandname' => 'EVM'
        ];
        
        $response = $this->makeHttpRequest($url, json_encode($data), [
            'Content-Type: application/json'
        ]);
        
        if ($response['success']) {
            $result = json_decode($response['body'], true);
            
            if ($result['CodeResult'] === '100') {
                return [
                    'success' => true,
                    'message_id' => $result['SMSID'] ?? 'esms_' . uniqid(),
                    'status' => 'sent',
                    'provider' => 'esms'
                ];
            }
            
            throw new \Exception("eSMS error: " . $result['ErrorMessage']);
        }
        
        throw new \Exception("eSMS API error: " . $response['error']);
    }
    
    /**
     * Mock SMS sending for development
     */
    private function sendMock($to, $message)
    {
        // Log SMS for development
        $logEntry = [
            'timestamp' => date('c'),
            'to' => $to,
            'message' => $message,
            'provider' => 'mock'
        ];
        
        error_log("SMS LOG: " . json_encode($logEntry, JSON_UNESCAPED_UNICODE));
        
        // Write to log file
        $logFile = __DIR__ . '/../../logs/sms.log';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        
        return [
            'success' => true,
            'message_id' => 'mock_' . uniqid(),
            'status' => 'sent',
            'provider' => 'mock'
        ];
    }
    
    /**
     * Make HTTP request
     */
    private function makeHttpRequest($url, $data, $headers = [])
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data) : $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge([
                'User-Agent: EVM-Notification-Service/1.0'
            ], $headers)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => "cURL error: {$error}"
            ];
        }
        
        if ($httpCode >= 400) {
            return [
                'success' => false,
                'error' => "HTTP error: {$httpCode}"
            ];
        }
        
        return [
            'success' => true,
            'body' => $response,
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Clean phone number
     */
    private function cleanPhoneNumber($phone)
    {
        // Remove all non-digit characters except +
        $clean = preg_replace('/[^\d+]/', '', $phone);
        
        // Handle Vietnamese phone numbers
        if (preg_match('/^0[0-9]{9}$/', $clean)) {
            // Convert 0xxxxxxxxx to +84xxxxxxxxx
            $clean = '+84' . substr($clean, 1);
        } elseif (preg_match('/^84[0-9]{9}$/', $clean)) {
            // Add + to 84xxxxxxxxx
            $clean = '+' . $clean;
        } elseif (preg_match('/^[0-9]{10}$/', $clean) && !str_starts_with($clean, '+')) {
            // Assume missing country code, add +84
            $clean = '+84' . $clean;
        }
        
        return $clean;
    }
    
    /**
     * Validate phone number
     */
    private function isValidPhoneNumber($phone)
    {
        // Basic international phone number validation
        return preg_match('/^\+[1-9]\d{6,14}$/', $phone);
    }
    
    /**
     * Truncate message to SMS limits
     */
    private function truncateMessage($message, $limit = 160)
    {
        if (mb_strlen($message, 'UTF-8') <= $limit) {
            return $message;
        }
        
        // Truncate and add ellipsis
        return mb_substr($message, 0, $limit - 3, 'UTF-8') . '...';
    }
    
    /**
     * Get SMS templates
     */
    public function getTemplates()
    {
        return [
            'notification' => 'EVM: {message}',
            'appointment' => 'EVM: Lich hen {date} {time}. {message}',
            'reminder' => 'EVM: Nhac nho {type}. {message}',
            'alert' => 'EVM CANH BAO: {message}',
            'promotion' => 'EVM KM: {message} Chi tiet: {link}'
        ];
    }
    
    /**
     * Render SMS template
     */
    public function renderTemplate($template, $data)
    {
        $templates = $this->getTemplates();
        
        if (!isset($templates[$template])) {
            return $data['message'] ?? '';
        }
        
        $message = $templates[$template];
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        // Remove unreplaced placeholders
        $message = preg_replace('/\{[^}]+\}/', '', $message);
        
        return trim($message);
    }
    
    /**
     * Check SMS delivery status
     */
    public function checkDeliveryStatus($messageId, $provider)
    {
        // Mock implementation - in real app, this would check with SMS provider
        return [
            'message_id' => $messageId,
            'status' => 'delivered',
            'provider' => $provider,
            'delivered_at' => date('c')
        ];
    }
}