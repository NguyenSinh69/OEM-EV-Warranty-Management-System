<?php

namespace App\Http\Controllers;

class CampaignController
{
    private $db;
    
    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }
    
    /**
     * POST /api/notifications/campaign - Thông báo campaign
     */
    public function create()
    {
        try {
            $data = getJsonInput();
            
            // Validate required fields
            $required = ['name', 'type', 'title', 'message', 'target_criteria'];
            $missing = validateRequired($data, $required);
            
            if (!empty($missing)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'missing_fields' => $missing
                ], 422);
            }
            
            // Validate enum values
            $validTypes = ['marketing', 'maintenance_reminder', 'recall_notice', 'promotion', 'system_update', 'warranty_expiry'];
            $validPriorities = ['low', 'medium', 'high'];
            
            if (!in_array($data['type'], $validTypes)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid campaign type',
                    'valid_types' => $validTypes
                ], 422);
            }
            
            $priority = $data['priority'] ?? 'medium';
            if (!in_array($priority, $validPriorities)) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Invalid priority',
                    'valid_priorities' => $validPriorities
                ], 422);
            }
            
            // Estimate recipients based on criteria
            $estimatedRecipients = $this->estimateRecipients($data['target_criteria']);
            
            // Create campaign
            $sql = "INSERT INTO notification_campaigns (
                name, description, type, title, message, 
                email_template, sms_template, target_criteria, 
                estimated_recipients, scheduled_at, start_date, 
                end_date, priority, created_by, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['type'],
                $data['title'],
                $data['message'],
                $data['email_template'] ?? null,
                $data['sms_template'] ?? null,
                json_encode($data['target_criteria']),
                $estimatedRecipients,
                $data['scheduled_at'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $priority,
                $data['created_by'] ?? 1,
                $data['status'] ?? 'draft'
            ]);
            
            $campaignId = $this->db->lastInsertId();
            
            // If campaign is set to start immediately
            if (isset($data['start_immediately']) && $data['start_immediately']) {
                $this->launchCampaign($campaignId);
            }
            
            // Get created campaign
            $campaign = $this->getCampaignById($campaignId);
            
            return jsonResponse([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Campaign creation error: " . $e->getMessage());
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/campaigns - Danh sách campaigns
     */
    public function index()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $status = $_GET['status'] ?? null;
            $type = $_GET['type'] ?? null;
            
            $offset = ($page - 1) * $limit;
            
            // Build query
            $whereConditions = [];
            $params = [];
            
            if ($status) {
                $whereConditions[] = 'status = ?';
                $params[] = $status;
            }
            
            if ($type) {
                $whereConditions[] = 'type = ?';
                $params[] = $type;
            }
            
            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Get campaigns
            $sql = "SELECT 
                c.*,
                (total_delivered / GREATEST(total_sent, 1) * 100) as delivery_rate,
                (total_opened / GREATEST(total_delivered, 1) * 100) as open_rate,
                (total_clicked / GREATEST(total_opened, 1) * 100) as click_rate
            FROM notification_campaigns c 
            $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
            
            $params[] = (int) $limit;
            $params[] = (int) $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $campaigns = $stmt->fetchAll();
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM notification_campaigns $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetch()['total'];
            
            // Get summary stats
            $stats = $this->getCampaignStats();
            
            return jsonResponse([
                'success' => true,
                'data' => [
                    'campaigns' => $campaigns,
                    'pagination' => [
                        'current_page' => (int) $page,
                        'per_page' => (int) $limit,
                        'total' => (int) $total,
                        'total_pages' => ceil($total / $limit)
                    ],
                    'stats' => $stats
                ],
                'message' => 'Campaigns retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to get campaigns',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT /api/campaigns/{id}/launch - Khởi chạy campaign
     */
    public function launch($campaignId)
    {
        try {
            return $this->launchCampaign($campaignId);
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to launch campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT /api/campaigns/{id}/pause - Tạm dừng campaign
     */
    public function pause($campaignId)
    {
        try {
            $sql = "UPDATE notification_campaigns SET status = 'paused' WHERE id = ? AND status = 'running'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$campaignId]);
            
            if ($stmt->rowCount() === 0) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Campaign not found or not running'
                ], 404);
            }
            
            return jsonResponse([
                'success' => true,
                'message' => 'Campaign paused successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to pause campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/campaigns/{id}/analytics - Phân tích campaign
     */
    public function analytics($campaignId)
    {
        try {
            $campaign = $this->getCampaignById($campaignId);
            
            if (!$campaign) {
                return jsonResponse([
                    'success' => false,
                    'message' => 'Campaign not found'
                ], 404);
            }
            
            // Get detailed analytics
            $analytics = [
                'overview' => [
                    'total_sent' => $campaign['total_sent'],
                    'total_delivered' => $campaign['total_delivered'],
                    'total_opened' => $campaign['total_opened'],
                    'total_clicked' => $campaign['total_clicked'],
                    'total_failed' => $campaign['total_failed'],
                    'delivery_rate' => $campaign['total_sent'] > 0 ? ($campaign['total_delivered'] / $campaign['total_sent'] * 100) : 0,
                    'open_rate' => $campaign['total_delivered'] > 0 ? ($campaign['total_opened'] / $campaign['total_delivered'] * 100) : 0,
                    'click_rate' => $campaign['total_opened'] > 0 ? ($campaign['total_clicked'] / $campaign['total_opened'] * 100) : 0
                ],
                'timeline' => $this->getCampaignTimeline($campaignId),
                'channels' => $this->getCampaignChannelStats($campaignId),
                'errors' => $this->getCampaignErrors($campaignId)
            ];
            
            return jsonResponse([
                'success' => true,
                'data' => [
                    'campaign' => $campaign,
                    'analytics' => $analytics
                ],
                'message' => 'Campaign analytics retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return jsonResponse([
                'success' => false,
                'message' => 'Failed to get campaign analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper methods
     */
    private function estimateRecipients($targetCriteria)
    {
        // Mock estimation logic - in real app, this would query customer database
        $criteria = is_string($targetCriteria) ? json_decode($targetCriteria, true) : $targetCriteria;
        
        // Simple estimation based on criteria
        $baseCount = 1000; // Mock total customers
        
        if (isset($criteria['vehicle_model'])) {
            $baseCount = $baseCount * 0.3; // 30% have specific model
        }
        
        if (isset($criteria['location'])) {
            $baseCount = $baseCount * 0.2; // 20% in specific location
        }
        
        if (isset($criteria['last_service'])) {
            $baseCount = $baseCount * 0.4; // 40% due for service
        }
        
        return max(1, (int) $baseCount);
    }
    
    private function launchCampaign($campaignId)
    {
        $campaign = $this->getCampaignById($campaignId);
        
        if (!$campaign) {
            return jsonResponse([
                'success' => false,
                'message' => 'Campaign not found'
            ], 404);
        }
        
        if ($campaign['status'] !== 'draft' && $campaign['status'] !== 'scheduled') {
            return jsonResponse([
                'success' => false,
                'message' => 'Campaign cannot be launched from current status',
                'current_status' => $campaign['status']
            ], 400);
        }
        
        // Update campaign status
        $sql = "UPDATE notification_campaigns SET status = 'running' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaignId]);
        
        // Queue campaign notifications
        $recipients = $this->getTargetRecipients($campaign['target_criteria']);
        $queued = $this->queueCampaignNotifications($campaignId, $recipients);
        
        // Update estimated recipients with actual count
        $updateSql = "UPDATE notification_campaigns SET estimated_recipients = ? WHERE id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([count($recipients), $campaignId]);
        
        return jsonResponse([
            'success' => true,
            'message' => 'Campaign launched successfully',
            'data' => [
                'campaign_id' => $campaignId,
                'recipients_queued' => $queued,
                'total_recipients' => count($recipients)
            ]
        ]);
    }
    
    private function getTargetRecipients($targetCriteria)
    {
        // Mock recipient selection - in real app, this would query customer database
        $criteria = is_string($targetCriteria) ? json_decode($targetCriteria, true) : $targetCriteria;
        
        // Return mock recipients based on criteria
        $mockRecipients = [
            ['id' => 1, 'email' => 'customer1@example.com', 'phone' => '+84901234567'],
            ['id' => 2, 'email' => 'customer2@example.com', 'phone' => '+84902345678'],
            ['id' => 3, 'email' => 'customer3@example.com', 'phone' => '+84903456789']
        ];
        
        return array_slice($mockRecipients, 0, rand(1, count($mockRecipients)));
    }
    
    private function queueCampaignNotifications($campaignId, $recipients)
    {
        $queued = 0;
        
        foreach ($recipients as $recipient) {
            // Queue email notification
            if (!empty($recipient['email'])) {
                $sql = "INSERT INTO notification_queue (
                    campaign_id, recipient_type, recipient_id, channel,
                    subject, message, recipient_email, status, scheduled_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $campaignId,
                    'customer',
                    $recipient['id'],
                    'email',
                    'Campaign Notification',
                    'Campaign message here',
                    $recipient['email'],
                    'pending',
                    date('Y-m-d H:i:s')
                ]);
                $queued++;
            }
            
            // Queue SMS notification if phone available
            if (!empty($recipient['phone'])) {
                $sql = "INSERT INTO notification_queue (
                    campaign_id, recipient_type, recipient_id, channel,
                    message, recipient_phone, status, scheduled_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $campaignId,
                    'customer',
                    $recipient['id'],
                    'sms',
                    'SMS campaign message',
                    $recipient['phone'],
                    'pending',
                    date('Y-m-d H:i:s')
                ]);
                $queued++;
            }
        }
        
        return $queued;
    }
    
    private function getCampaignById($id)
    {
        $sql = "SELECT * FROM notification_campaigns WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    private function getCampaignStats()
    {
        $sql = "SELECT 
            COUNT(*) as total_campaigns,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as running,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused,
            SUM(total_sent) as total_sent,
            SUM(total_delivered) as total_delivered,
            AVG(total_opened / GREATEST(total_delivered, 1) * 100) as avg_open_rate
        FROM notification_campaigns";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
    
    private function getCampaignTimeline($campaignId)
    {
        // Mock timeline data
        return [
            ['date' => '2024-11-01', 'sent' => 100, 'delivered' => 95, 'opened' => 45],
            ['date' => '2024-11-02', 'sent' => 150, 'delivered' => 142, 'opened' => 68],
            ['date' => '2024-11-03', 'sent' => 200, 'delivered' => 188, 'opened' => 89]
        ];
    }
    
    private function getCampaignChannelStats($campaignId)
    {
        $sql = "SELECT 
            channel,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM notification_queue 
        WHERE campaign_id = ? 
        GROUP BY channel";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }
    
    private function getCampaignErrors($campaignId)
    {
        $sql = "SELECT 
            error_message,
            COUNT(*) as count,
            channel
        FROM notification_queue 
        WHERE campaign_id = ? AND status = 'failed' AND error_message IS NOT NULL
        GROUP BY error_message, channel
        ORDER BY count DESC
        LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }
}