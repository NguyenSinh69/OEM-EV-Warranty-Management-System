<?php

namespace App\Models;

class WarrantyPolicy
{
    private $db;
    private $table = 'warranty_policies';
    
    public function __construct($database) 
    {
        $this->db = $database;
    }
    
    /**
     * Create a new warranty policy
     */
    public function create($data) 
    {
        try {
            $sql = "INSERT INTO {$this->table} (
                component_id, policy_name, warranty_duration, 
                coverage_details, conditions, exclusions, 
                effective_date, expiry_date, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['component_id'],
                $data['policy_name'],
                $data['warranty_duration'],
                json_encode($data['coverage_details'] ?? []),
                json_encode($data['conditions'] ?? []),
                json_encode($data['exclusions'] ?? []),
                $data['effective_date'],
                $data['expiry_date'] ?? null,
                $data['status'] ?? 'active',
                $data['created_by'] ?? null
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("WarrantyPolicy create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all warranty policies with optional filters
     */
    public function getAll($filters = []) 
    {
        try {
            $sql = "SELECT wp.*, ec.component_name, ec.component_type, ec.model 
                    FROM {$this->table} wp
                    LEFT JOIN ev_components ec ON wp.component_id = ec.id
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['component_id'])) {
                $sql .= " AND wp.component_id = ?";
                $params[] = $filters['component_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND wp.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['component_type'])) {
                $sql .= " AND ec.component_type = ?";
                $params[] = $filters['component_type'];
            }
            
            $sql .= " ORDER BY wp.created_at DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($policies as &$policy) {
                $policy['coverage_details'] = json_decode($policy['coverage_details'], true);
                $policy['conditions'] = json_decode($policy['conditions'], true);
                $policy['exclusions'] = json_decode($policy['exclusions'], true);
            }
            
            return $policies;
        } catch (Exception $e) {
            error_log("WarrantyPolicy getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get warranty policy by ID
     */
    public function getById($id) 
    {
        try {
            $sql = "SELECT wp.*, ec.component_name, ec.component_type, ec.model 
                    FROM {$this->table} wp
                    LEFT JOIN ev_components ec ON wp.component_id = ec.id
                    WHERE wp.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $policy = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($policy) {
                $policy['coverage_details'] = json_decode($policy['coverage_details'], true);
                $policy['conditions'] = json_decode($policy['conditions'], true);
                $policy['exclusions'] = json_decode($policy['exclusions'], true);
            }
            
            return $policy;
        } catch (Exception $e) {
            error_log("WarrantyPolicy getById error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update warranty policy
     */
    public function update($id, $data) 
    {
        try {
            $sql = "UPDATE {$this->table} SET 
                component_id = ?, policy_name = ?, warranty_duration = ?, 
                coverage_details = ?, conditions = ?, exclusions = ?, 
                effective_date = ?, expiry_date = ?, status = ?, 
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['component_id'],
                $data['policy_name'],
                $data['warranty_duration'],
                json_encode($data['coverage_details'] ?? []),
                json_encode($data['conditions'] ?? []),
                json_encode($data['exclusions'] ?? []),
                $data['effective_date'],
                $data['expiry_date'] ?? null,
                $data['status'] ?? 'active',
                $id
            ]);
        } catch (Exception $e) {
            error_log("WarrantyPolicy update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete warranty policy
     */
    public function delete($id) 
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("WarrantyPolicy delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get policies by component ID
     */
    public function getByComponentId($componentId) 
    {
        return $this->getAll(['component_id' => $componentId]);
    }
    
    /**
     * Get active policies
     */
    public function getActivePolicies() 
    {
        return $this->getAll(['status' => 'active']);
    }
    
    /**
     * Check if component has active warranty policy
     */
    public function hasActivePolicy($componentId) 
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE component_id = ? AND status = 'active' 
                    AND (expiry_date IS NULL OR expiry_date > CURDATE())
                    AND effective_date <= CURDATE()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$componentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("WarrantyPolicy hasActivePolicy error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get expiring policies (within next 30 days)
     */
    public function getExpiringPolicies($days = 30) 
    {
        try {
            $sql = "SELECT wp.*, ec.component_name, ec.component_type 
                    FROM {$this->table} wp
                    LEFT JOIN ev_components ec ON wp.component_id = ec.id
                    WHERE wp.expiry_date IS NOT NULL 
                    AND wp.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    AND wp.status = 'active'
                    ORDER BY wp.expiry_date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("WarrantyPolicy getExpiringPolicies error: " . $e->getMessage());
            return [];
        }
    }
}