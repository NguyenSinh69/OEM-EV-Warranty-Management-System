<?php

namespace App\Models;

class EVComponent
{
    private $db;
    private $table = 'ev_components';
    
    public function __construct($database) 
    {
        $this->db = $database;
    }
    
    /**
     * Create a new EV component
     */
    public function create($data) 
    {
        try {
            $sql = "INSERT INTO {$this->table} (
                component_type, component_name, model, specifications, 
                warranty_period, supplier_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['component_type'],
                $data['component_name'],
                $data['model'],
                json_encode($data['specifications'] ?? []),
                $data['warranty_period'],
                $data['supplier_id'] ?? null,
                $data['status'] ?? 'active'
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("EVComponent create error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all components with optional filters
     */
    public function getAll($filters = []) 
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            
            if (!empty($filters['component_type'])) {
                $sql .= " AND component_type = ?";
                $params[] = $filters['component_type'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['model'])) {
                $sql .= " AND model LIKE ?";
                $params[] = "%{$filters['model']}%";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON specifications
            foreach ($components as &$component) {
                $component['specifications'] = json_decode($component['specifications'], true);
            }
            
            return $components;
        } catch (Exception $e) {
            error_log("EVComponent getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get component by ID
     */
    public function getById($id) 
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $component = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($component) {
                $component['specifications'] = json_decode($component['specifications'], true);
            }
            
            return $component;
        } catch (Exception $e) {
            error_log("EVComponent getById error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update component
     */
    public function update($id, $data) 
    {
        try {
            $sql = "UPDATE {$this->table} SET 
                component_type = ?, component_name = ?, model = ?, 
                specifications = ?, warranty_period = ?, supplier_id = ?, 
                status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['component_type'],
                $data['component_name'],
                $data['model'],
                json_encode($data['specifications'] ?? []),
                $data['warranty_period'],
                $data['supplier_id'] ?? null,
                $data['status'] ?? 'active',
                $id
            ]);
        } catch (Exception $e) {
            error_log("EVComponent update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete component
     */
    public function delete($id) 
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("EVComponent delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get components by type
     */
    public function getByType($componentType) 
    {
        return $this->getAll(['component_type' => $componentType]);
    }
    
    /**
     * Get component statistics
     */
    public function getStatistics() 
    {
        try {
            $sql = "SELECT 
                component_type,
                COUNT(*) as total_count,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
                COUNT(CASE WHEN status = 'discontinued' THEN 1 END) as discontinued_count,
                COUNT(CASE WHEN status = 'recalled' THEN 1 END) as recalled_count,
                AVG(warranty_period) as avg_warranty_period
                FROM {$this->table}
                GROUP BY component_type";
                
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("EVComponent getStatistics error: " . $e->getMessage());
            return [];
        }
    }
}