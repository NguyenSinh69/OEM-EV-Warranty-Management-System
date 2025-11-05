<?php

namespace App\Services;

class QueueService
{
    private $db;
    private $emailService;
    private $smsService;
    
    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
    }
    
    /**
     * Add notification to queue
     */
    public function addToQueue($channel, $notificationId, $recipient, $scheduledAt = null)
    {
        try {
            // Get notification details
            $notification = $this->getNotificationById($notificationId);
            if (!$notification) {
                throw new \Exception("Notification not found: {$notificationId}");
            }
            
            $sql = "INSERT INTO notification_queue (
                notification_id, recipient_type, recipient_id, channel,
                subject, message, recipient_email, recipient_phone,
                status, scheduled_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $notificationId,
                'customer', // Assuming customer for now
                $recipient['id'],
                $channel,
                $notification['title'],
                $notification['message'],
                $channel === 'email' ? $recipient['email'] : null,
                $channel === 'sms' ? $recipient['phone'] : null,
                'pending',
                $scheduledAt ?? date('Y-m-d H:i:s')
            ]);
            
            $queueId = $this->db->lastInsertId();
            
            // If not scheduled for later, process immediately
            if (!$scheduledAt || strtotime($scheduledAt) <= time()) {
                $this->processQueueItem($queueId);
            }
            
            return $queueId;
            
        } catch (\Exception $e) {
            error_log("Queue add error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process queue items
     */
    public function processQueue($limit = 50)
    {
        try {
            // Get pending queue items
            $sql = "SELECT * FROM notification_queue 
                    WHERE status = 'pending' 
                    AND scheduled_at <= NOW()
                    AND attempts < max_attempts
                    ORDER BY scheduled_at ASC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            $queueItems = $stmt->fetchAll();
            
            $processed = 0;
            $errors = [];
            
            foreach ($queueItems as $item) {
                try {
                    $this->processQueueItem($item['id']);
                    $processed++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'queue_id' => $item['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return [
                'processed' => $processed,
                'total' => count($queueItems),
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            error_log("Queue processing error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process single queue item
     */
    public function processQueueItem($queueId)
    {
        try {
            // Get queue item
            $sql = "SELECT * FROM notification_queue WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$queueId]);
            $item = $stmt->fetch();
            
            if (!$item) {
                throw new \Exception("Queue item not found: {$queueId}");
            }
            
            if ($item['status'] !== 'pending') {
                return; // Already processed
            }
            
            // Update status to processing
            $this->updateQueueStatus($queueId, 'processing');
            
            $result = null;
            
            switch ($item['channel']) {
                case 'email':
                    if (!$item['recipient_email']) {
                        throw new \Exception("No email address for queue item {$queueId}");
                    }
                    
                    $result = $this->emailService->send(
                        $item['recipient_email'],
                        $item['subject'],
                        $item['message']
                    );
                    break;
                    
                case 'sms':
                    if (!$item['recipient_phone']) {
                        throw new \Exception("No phone number for queue item {$queueId}");
                    }
                    
                    $result = $this->smsService->send(
                        $item['recipient_phone'],
                        $item['message']
                    );
                    break;
                    
                default:
                    throw new \Exception("Unsupported channel: {$item['channel']}");
            }
            
            if ($result['success']) {
                // Mark as sent
                $this->updateQueueStatus($queueId, 'sent', null, $result['message_id'] ?? null);
                
                // Update notification status
                if ($item['notification_id']) {
                    $this->updateNotificationDelivery($item['notification_id'], $item['channel']);
                }
                
            } else {
                throw new \Exception($result['error'] ?? 'Unknown error');
            }
            
        } catch (\Exception $e) {
            // Mark as failed and increment attempts
            $this->updateQueueStatus($queueId, 'failed', $e->getMessage());
            $this->incrementAttempts($queueId);
            
            error_log("Queue item {$queueId} failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update queue item status
     */
    private function updateQueueStatus($queueId, $status, $errorMessage = null, $messageId = null)
    {
        $sql = "UPDATE notification_queue SET 
                status = ?, 
                error_message = ?,
                sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE sent_at END,
                last_attempt_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $errorMessage, $status, $queueId]);
        
        if ($messageId) {
            // Store message ID in a separate field if available
            $updateSql = "UPDATE notification_queue SET 
                         message = CONCAT(message, '\n[Message ID: ', ?, ']')
                         WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$messageId, $queueId]);
        }
    }
    
    /**
     * Increment attempt count
     */
    private function incrementAttempts($queueId)
    {
        $sql = "UPDATE notification_queue SET attempts = attempts + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$queueId]);
    }
    
    /**
     * Update notification delivery status
     */
    private function updateNotificationDelivery($notificationId, $channel)
    {
        $updateFields = [];
        
        switch ($channel) {
            case 'email':
                $updateFields[] = 'email_sent_at = NOW()';
                break;
            case 'sms':
                $updateFields[] = 'sms_sent_at = NOW()';
                break;
        }
        
        if (!empty($updateFields)) {
            $updateFields[] = 'status = "sent"';
            $updateFields[] = 'delivered_at = NOW()';
            
            $sql = "UPDATE notifications SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$notificationId]);
        }
    }
    
    /**
     * Get notification by ID
     */
    private function getNotificationById($id)
    {
        $sql = "SELECT * FROM notifications WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Retry failed queue items
     */
    public function retryFailed($hours = 1)
    {
        try {
            // Reset failed items that are not too old and haven't exceeded max attempts
            $sql = "UPDATE notification_queue SET 
                    status = 'pending',
                    error_message = NULL,
                    scheduled_at = NOW()
                    WHERE status = 'failed' 
                    AND attempts < max_attempts
                    AND last_attempt_at > DATE_SUB(NOW(), INTERVAL ? HOUR)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hours]);
            
            $retried = $stmt->rowCount();
            
            return [
                'retried' => $retried,
                'message' => "Retried {$retried} failed queue items"
            ];
            
        } catch (\Exception $e) {
            error_log("Retry failed error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Clean old queue items
     */
    public function cleanOldItems($days = 30)
    {
        try {
            // Delete old completed/failed items
            $sql = "DELETE FROM notification_queue 
                    WHERE status IN ('sent', 'failed', 'cancelled')
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            
            $deleted = $stmt->rowCount();
            
            return [
                'deleted' => $deleted,
                'message' => "Cleaned {$deleted} old queue items"
            ];
            
        } catch (\Exception $e) {
            error_log("Clean old items error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats()
    {
        try {
            $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                AVG(CASE WHEN status = 'sent' AND sent_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, created_at, sent_at) 
                    ELSE NULL END) as avg_processing_time_seconds
            FROM notification_queue
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
            $stmt = $this->db->query($sql);
            $stats = $stmt->fetch();
            
            // Add channel breakdown
            $channelSql = "SELECT 
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM notification_queue
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY channel";
            
            $channelStmt = $this->db->query($channelSql);
            $channelStats = $channelStmt->fetchAll();
            
            return [
                'overview' => $stats,
                'by_channel' => $channelStats,
                'period' => 'last_24_hours'
            ];
            
        } catch (\Exception $e) {
            error_log("Get queue stats error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Schedule recurring cleanup job
     */
    public function scheduleCleanup()
    {
        // This would integrate with a job scheduler like cron
        // For now, just clean if it's been more than 24 hours since last cleanup
        
        $lastCleanup = $this->getLastCleanupTime();
        $hoursSinceCleanup = (time() - $lastCleanup) / 3600;
        
        if ($hoursSinceCleanup >= 24) {
            $this->cleanOldItems();
            $this->setLastCleanupTime(time());
        }
    }
    
    private function getLastCleanupTime()
    {
        // Simple file-based tracking
        $file = __DIR__ . '/../../logs/last_cleanup.txt';
        if (file_exists($file)) {
            return (int) file_get_contents($file);
        }
        return 0;
    }
    
    private function setLastCleanupTime($timestamp)
    {
        $file = __DIR__ . '/../../logs/last_cleanup.txt';
        file_put_contents($file, $timestamp);
    }
}