<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use App\Services\SMSService;
use App\Services\QueueService;

class NotificationController
{
    private $db;
    private $emailService;
    private $smsService;
    private $queueService;
    
    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
        $this->emailService = new EmailService();
        $this->smsService = new SMSService();
        $this->queueService = new QueueService();
    }
    
    /**
     * POST /api/notifications/send - Gửi thông báo
     */
    public function send()
    {
        try {
            $data = getJsonInput();
            
            // Validate required fields
            $required = ['customer_id', 'title', 'message', 'type'];
            $missing = validateRequired($data, $required);
            
            if (!empty($missing)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'missing_fields' => $missing
                ], 422);
            }
            
            // Default values
            $channels = $data['channels'] ?? ['in_app'];
            $priority = $data['priority'] ?? 'medium';
            $scheduledAt = $data['scheduled_at'] ?? null;
            $expiresAt = $data['expires_at'] ?? null;
            
            // Validate enum values
            $validTypes = ['info', 'warning', 'success', 'error', 'warranty_claim', 'appointment', 'maintenance', 'campaign'];
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            
            if (!in_array($data['type'], $validTypes)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid notification type',
                    'valid_types' => $validTypes
                ], 422);
            }
            
            if (!in_array($priority, $validPriorities)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid priority',
                    'valid_priorities' => $validPriorities
                ], 422);
            }
            
            // Get customer information
            $customer = $this->getCustomerInfo($data['customer_id']);
            if (!$customer) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }
            
            // Create notification record
            $sql = "INSERT INTO notifications (
                customer_id, title, message, type, priority, channels, data,
                related_type, related_id, scheduled_at, expires_at, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['customer_id'],
                $data['title'],
                $data['message'],
                $data['type'],
                $priority,
                json_encode($channels),
                isset($data['data']) ? json_encode($data['data']) : null,
                $data['related_type'] ?? null,
                $data['related_id'] ?? null,
                $scheduledAt,
                $expiresAt,
                $scheduledAt ? 'pending' : 'sent'
            ]);
            
            $notificationId = $this->db->lastInsertId();
            
            // Queue notification for delivery
            $this->queueNotificationDelivery($notificationId, $customer, $channels, $scheduledAt);
            
            // Get created notification
            $notification = $this->getNotificationById($notificationId);
            
            return jsonResponse([
                'success' => true,
                'message' => $scheduledAt ? 'Notification scheduled successfully' : 'Notification sent successfully',
                'data' => $notification
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Notification send error: " . $e->getMessage());
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/notifications/{customer_id} - Lấy thông báo của khách hàng
     */
    public function getByCustomer($customerId)
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $status = $_GET['status'] ?? null;
            $type = $_GET['type'] ?? null;
            $unread_only = $_GET['unread_only'] ?? false;
            
            $offset = ($page - 1) * $limit;
            
            // Build query
            $whereConditions = ['customer_id = ?'];
            $params = [$customerId];
            
            if ($status) {
                $whereConditions[] = 'status = ?';
                $params[] = $status;
            }
            
            if ($type) {
                $whereConditions[] = 'type = ?';
                $params[] = $type;
            }
            
            if ($unread_only) {
                $whereConditions[] = 'read_at IS NULL';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get notifications
            $sql = "SELECT * FROM notifications 
                    WHERE $whereClause 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $params[] = (int) $limit;
            $params[] = (int) $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll();
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM notifications WHERE $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
            $total = $countStmt->fetch()['total'];
            
            // Get unread count
            $unreadSql = "SELECT COUNT(*) as unread FROM notifications 
                         WHERE customer_id = ? AND read_at IS NULL";
            $unreadStmt = $this->db->prepare($unreadSql);
            $unreadStmt->execute([$customerId]);
            $unreadCount = $unreadStmt->fetch()['unread'];
            
            return jsonResponse([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'pagination' => [
                        'current_page' => (int) $page,
                        'per_page' => (int) $limit,
                        'total' => (int) $total,
                        'total_pages' => ceil($total / $limit)
                    ],
                    'unread_count' => (int) $unreadCount
                ],
                'message' => 'Notifications retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to get notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT /api/notifications/{id}/read - Đánh dấu đã đọc
     */
    public function markAsRead($notificationId)
    {
        try {
            $sql = "UPDATE notifications SET read_at = NOW() WHERE id = ? AND read_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$notificationId]);
            
            if ($stmt->rowCount() === 0) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Notification not found or already read'
                ], 404);
            }
            
            return jsonResponse([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper methods
     */
    private function getCustomerInfo($customerId)
    {
        // Mock customer data - in real app, call customer service API
        $customers = [
            1 => [
                'id' => 1,
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'phone' => '+84901234567'
            ],
            2 => [
                'id' => 2,
                'name' => 'Trần Thị B',
                'email' => 'tranthib@example.com',
                'phone' => '+84902345678'
            ]
        ];
        
        return $customers[$customerId] ?? null;
    }
    
    private function getNotificationById($id)
    {
        $sql = "SELECT * FROM notifications WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    private function queueNotificationDelivery($notificationId, $customer, $channels, $scheduledAt)
    {
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    if ($customer['email']) {
                        $this->queueService->addToQueue(
                            'email',
                            $notificationId,
                            $customer,
                            $scheduledAt
                        );
                    }
                    break;
                    
                case 'sms':
                    if ($customer['phone']) {
                        $this->queueService->addToQueue(
                            'sms',
                            $notificationId,
                            $customer,
                            $scheduledAt
                        );
                    }
                    break;
                    
                case 'in_app':
                    // In-app notifications are already stored in database
                    break;
            }
        }
    }
}