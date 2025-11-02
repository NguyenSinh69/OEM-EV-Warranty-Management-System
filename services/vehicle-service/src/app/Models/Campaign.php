<?php

namespace App\Models;

class Campaign
{
    private $db;
    private $table = 'campaigns';
    private $progressTable = 'campaign_progress';
    
    public function __construct($database) 
    {
        $this->db = $database;
    }
    
    /**
     * Create a new campaign
     */
    public function create($data) 
    {
        try {
            $sql = "INSERT INTO {$this->table} (
                title, description, campaign_type, affected_models, 
                affected_vins, affected_components, priority_level, 
                start_date, end_date, instructions, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['campaign_type'],
                json_encode($data['affected_models'] ?? []),
                json_encode($data['affected_vins'] ?? []),
                json_encode($data['affected_components'] ?? []),
                $data['priority_level'] ?? 'medium',
                $data['start_date'],
                $data['end_date'] ?? null,
                $data['instructions'] ?? '',
                $data['status'] ?? 'draft',
                $data['created_by'] ?? null
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Campaign create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all campaigns with optional filters
     */
    public function getAll($filters = []) 
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            
            if (!empty($filters['campaign_type'])) {
                $sql .= " AND campaign_type = ?";
                $params[] = $filters['campaign_type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['priority_level'])) {
                $sql .= " AND priority_level = ?";
                $params[] = $filters['priority_level'];
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($campaigns as &$campaign) {
                $campaign['affected_models'] = json_decode($campaign['affected_models'], true);
                $campaign['affected_vins'] = json_decode($campaign['affected_vins'], true);
                $campaign['affected_components'] = json_decode($campaign['affected_components'], true);
            }
            
            return $campaigns;
        } catch (Exception $e) {
            error_log("Campaign getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get campaign by ID
     */
    public function getById($id) 
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($campaign) {
                $campaign['affected_models'] = json_decode($campaign['affected_models'], true);
                $campaign['affected_vins'] = json_decode($campaign['affected_vins'], true);
                $campaign['affected_components'] = json_decode($campaign['affected_components'], true);
            }
            
            return $campaign;
        } catch (Exception $e) {
            error_log("Campaign getById error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update campaign
     */
    public function update($id, $data) 
    {
        try {
            $sql = "UPDATE {$this->table} SET 
                title = ?, description = ?, campaign_type = ?, 
                affected_models = ?, affected_vins = ?, affected_components = ?, 
                priority_level = ?, start_date = ?, end_date = ?, 
                instructions = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['campaign_type'],
                json_encode($data['affected_models'] ?? []),
                json_encode($data['affected_vins'] ?? []),
                json_encode($data['affected_components'] ?? []),
                $data['priority_level'] ?? 'medium',
                $data['start_date'],
                $data['end_date'] ?? null,
                $data['instructions'] ?? '',
                $data['status'] ?? 'draft',
                $id
            ]);
        } catch (Exception $e) {
            error_log("Campaign update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete campaign
     */
    public function delete($id) 
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Campaign delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get affected vehicles for a campaign
     * This would typically connect to customer-service to get actual vehicle data
     */
    public function getAffectedVehicles($campaignId) 
    {
        try {
            $campaign = $this->getById($campaignId);
            if (!$campaign) {
                return [];
            }
            
            // Mock data for affected vehicles - in real implementation, 
            // this would call customer-service API
            $mockVehicles = [
                [
                    'vin' => '1HGCM82633A123456',
                    'model' => 'Model-X-2024',
                    'year' => 2024,
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john.doe@email.com',
                    'customer_phone' => '+1234567890'
                ],
                [
                    'vin' => '1HGCM82633A123457',
                    'model' => 'Model-Y-2024', 
                    'year' => 2024,
                    'customer_name' => 'Jane Smith',
                    'customer_email' => 'jane.smith@email.com',
                    'customer_phone' => '+1234567891'
                ]
            ];
            
            // Filter by affected models
            $affectedVehicles = [];
            foreach ($mockVehicles as $vehicle) {
                if (in_array($vehicle['model'], $campaign['affected_models']) || 
                    in_array($vehicle['vin'], $campaign['affected_vins'] ?? [])) {
                    $affectedVehicles[] = $vehicle;
                }
            }
            
            return $affectedVehicles;
            
        } catch (Exception $e) {
            error_log("Campaign getAffectedVehicles error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get campaign progress
     */
    public function getProgress($campaignId) 
    {
        try {
            // Get overall campaign stats
            $sql = "SELECT 
                COUNT(*) as total_affected,
                COUNT(CASE WHEN status = 'identified' THEN 1 END) as identified,
                COUNT(CASE WHEN status = 'notified' THEN 1 END) as notified,
                COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
                FROM {$this->progressTable} WHERE campaign_id = ?";
                
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$campaignId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get detailed progress records
            $sql = "SELECT * FROM {$this->progressTable} 
                    WHERE campaign_id = ? 
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$campaignId]);
            $progressRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'statistics' => $stats,
                'progress_records' => $progressRecords
            ];
            
        } catch (Exception $e) {
            error_log("Campaign getProgress error: " . $e->getMessage());
            return [
                'statistics' => [],
                'progress_records' => []
            ];
        }
    }
    
    /**
     * Update campaign statistics
     */
    public function updateStatistics($campaignId) 
    {
        try {
            $progress = $this->getProgress($campaignId);
            $stats = $progress['statistics'];
            
            $sql = "UPDATE {$this->table} SET 
                total_affected_vehicles = ?,
                notified_customers = ?,
                completed_services = ?
                WHERE id = ?";
                
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $stats['total_affected'] ?? 0,
                $stats['notified'] ?? 0,
                $stats['completed'] ?? 0,
                $campaignId
            ]);
            
        } catch (Exception $e) {
            error_log("Campaign updateStatistics error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notify customers for campaign
     * This would integrate with notification-service
     */
    public function notifyCustomers($campaignId) 
    {
        try {
            $affectedVehicles = $this->getAffectedVehicles($campaignId);
            $campaign = $this->getById($campaignId);
            
            $notificationsSent = 0;
            
            foreach ($affectedVehicles as $vehicle) {
                // Mock notification sending - in real implementation, 
                // this would call notification-service API
                $notificationData = [
                    'customer_email' => $vehicle['customer_email'],
                    'campaign_title' => $campaign['title'],
                    'campaign_description' => $campaign['description'],
                    'vin' => $vehicle['vin'],
                    'instructions' => $campaign['instructions']
                ];
                
                // Mock successful notification
                if ($this->sendNotification($notificationData)) {
                    $notificationsSent++;
                    
                    // Add progress record
                    $this->addProgressRecord($campaignId, $vehicle['vin'], 'notified');
                }
            }
            
            // Update campaign statistics
            $this->updateStatistics($campaignId);
            
            return [
                'success' => true,
                'notifications_sent' => $notificationsSent,
                'total_affected' => count($affectedVehicles)
            ];
            
        } catch (Exception $e) {
            error_log("Campaign notifyCustomers error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Add progress record for campaign
     */
    private function addProgressRecord($campaignId, $vin, $status, $notes = null) 
    {
        try {
            $sql = "INSERT INTO {$this->progressTable} (
                campaign_id, vin, status, notes
            ) VALUES (?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$campaignId, $vin, $status, $notes]);
            
        } catch (Exception $e) {
            error_log("Campaign addProgressRecord error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mock notification sending
     */
    private function sendNotification($data) 
    {
        // In real implementation, this would call notification-service API
        // For now, just log the notification
        error_log("Sending notification to: " . $data['customer_email'] . 
                 " for campaign: " . $data['campaign_title']);
        return true;
    }
    
    /**
     * Get active campaigns
     */
    public function getActiveCampaigns() 
    {
        return $this->getAll(['status' => 'active']);
    }
    
    /**
     * Get campaigns by type
     */
    public function getByType($type) 
    {
        return $this->getAll(['campaign_type' => $type]);
    }
}