<?php

namespace App\Http\Controllers;

require_once __DIR__ . '/../../Models/EVComponent.php';
require_once __DIR__ . '/../../Models/WarrantyPolicy.php';
require_once __DIR__ . '/../../Models/Campaign.php';

use App\Models\EVComponent;
use App\Models\WarrantyPolicy;
use App\Models\Campaign;

class ComponentsController
{
    private $evComponent;
    private $warrantyPolicy;
    private $campaign;
    private $db;
    
    public function __construct($database) 
    {
        $this->db = $database;
        $this->evComponent = new EVComponent($database);
        $this->warrantyPolicy = new WarrantyPolicy($database);
        $this->campaign = new Campaign($database);
    }
    
    /**
     * Handle HTTP requests and route to appropriate methods
     */
    public function handleRequest($method, $uri, $data = null) 
    {
        // Parse URI to extract endpoint and parameters
        $uriParts = explode('/', trim($uri, '/'));
        
        // Remove 'api' from path if present
        if ($uriParts[0] === 'api') {
            array_shift($uriParts);
        }
        
        $endpoint = $uriParts[0] ?? '';
        $id = $uriParts[1] ?? null;
        $action = $uriParts[2] ?? null;
        
        try {
            switch ($endpoint) {
                case 'components':
                    return $this->handleComponentRequests($method, $id, $data);
                    
                case 'warranty-policies':
                    return $this->handleWarrantyPolicyRequests($method, $id, $data);
                    
                case 'campaigns':
                    return $this->handleCampaignRequests($method, $id, $action, $data);
                    
                default:
                    return $this->jsonResponse(['error' => 'Invalid endpoint'], 404);
            }
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * POST /api/components - Thêm linh kiện EV mới
     * GET /api/components - Danh sách linh kiện
     */
    private function handleComponentRequests($method, $id, $data) 
    {
        switch ($method) {
            case 'GET':
                if ($id) {
                    // GET /api/components/{id}
                    $component = $this->evComponent->getById($id);
                    if (!$component) {
                        return $this->jsonResponse(['error' => 'Component not found'], 404);
                    }
                    return $this->jsonResponse(['data' => $component]);
                } else {
                    // GET /api/components
                    $filters = $_GET ?? [];
                    $components = $this->evComponent->getAll($filters);
                    return $this->jsonResponse([
                        'data' => $components,
                        'total' => count($components)
                    ]);
                }
                
            case 'POST':
                // POST /api/components - Create new component
                if (!$this->validateComponentData($data)) {
                    return $this->jsonResponse(['error' => 'Invalid component data'], 400);
                }
                
                $componentId = $this->evComponent->create($data);
                if ($componentId) {
                    $component = $this->evComponent->getById($componentId);
                    return $this->jsonResponse([
                        'message' => 'Component created successfully',
                        'data' => $component
                    ], 201);
                }
                return $this->jsonResponse(['error' => 'Failed to create component'], 500);
                
            case 'PUT':
                // PUT /api/components/{id}
                if (!$id) {
                    return $this->jsonResponse(['error' => 'Component ID required'], 400);
                }
                
                if (!$this->evComponent->getById($id)) {
                    return $this->jsonResponse(['error' => 'Component not found'], 404);
                }
                
                if ($this->evComponent->update($id, $data)) {
                    $component = $this->evComponent->getById($id);
                    return $this->jsonResponse([
                        'message' => 'Component updated successfully',
                        'data' => $component
                    ]);
                }
                return $this->jsonResponse(['error' => 'Failed to update component'], 500);
                
            case 'DELETE':
                // DELETE /api/components/{id}
                if (!$id) {
                    return $this->jsonResponse(['error' => 'Component ID required'], 400);
                }
                
                if (!$this->evComponent->getById($id)) {
                    return $this->jsonResponse(['error' => 'Component not found'], 404);
                }
                
                if ($this->evComponent->delete($id)) {
                    return $this->jsonResponse(['message' => 'Component deleted successfully']);
                }
                return $this->jsonResponse(['error' => 'Failed to delete component'], 500);
                
            default:
                return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * POST /api/warranty-policies - Tạo chính sách bảo hành
     */
    private function handleWarrantyPolicyRequests($method, $id, $data) 
    {
        switch ($method) {
            case 'GET':
                if ($id) {
                    // GET /api/warranty-policies/{id}
                    $policy = $this->warrantyPolicy->getById($id);
                    if (!$policy) {
                        return $this->jsonResponse(['error' => 'Policy not found'], 404);
                    }
                    return $this->jsonResponse(['data' => $policy]);
                } else {
                    // GET /api/warranty-policies
                    $filters = $_GET ?? [];
                    $policies = $this->warrantyPolicy->getAll($filters);
                    return $this->jsonResponse([
                        'data' => $policies,
                        'total' => count($policies)
                    ]);
                }
                
            case 'POST':
                // POST /api/warranty-policies - Create new policy
                if (!$this->validateWarrantyPolicyData($data)) {
                    return $this->jsonResponse(['error' => 'Invalid warranty policy data'], 400);
                }
                
                $policyId = $this->warrantyPolicy->create($data);
                if ($policyId) {
                    $policy = $this->warrantyPolicy->getById($policyId);
                    return $this->jsonResponse([
                        'message' => 'Warranty policy created successfully',
                        'data' => $policy
                    ], 201);
                }
                return $this->jsonResponse(['error' => 'Failed to create warranty policy'], 500);
                
            default:
                return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * POST /api/campaigns - Tạo chiến dịch recall
     * GET /api/campaigns/{id}/vehicles - Xe bị ảnh hưởng
     * POST /api/campaigns/{id}/notify - Gửi thông báo  
     * GET /api/campaigns/{id}/progress - Tiến độ campaign
     */
    private function handleCampaignRequests($method, $id, $action, $data) 
    {
        switch ($method) {
            case 'GET':
                if ($id && $action === 'vehicles') {
                    // GET /api/campaigns/{id}/vehicles - Get affected vehicles
                    $vehicles = $this->campaign->getAffectedVehicles($id);
                    return $this->jsonResponse([
                        'data' => $vehicles,
                        'total' => count($vehicles)
                    ]);
                } elseif ($id && $action === 'progress') {
                    // GET /api/campaigns/{id}/progress - Get campaign progress
                    $progress = $this->campaign->getProgress($id);
                    return $this->jsonResponse(['data' => $progress]);
                } elseif ($id) {
                    // GET /api/campaigns/{id}
                    $campaign = $this->campaign->getById($id);
                    if (!$campaign) {
                        return $this->jsonResponse(['error' => 'Campaign not found'], 404);
                    }
                    return $this->jsonResponse(['data' => $campaign]);
                } else {
                    // GET /api/campaigns
                    $filters = $_GET ?? [];
                    $campaigns = $this->campaign->getAll($filters);
                    return $this->jsonResponse([
                        'data' => $campaigns,
                        'total' => count($campaigns)
                    ]);
                }
                
            case 'POST':
                if ($id && $action === 'notify') {
                    // POST /api/campaigns/{id}/notify - Send notifications
                    $result = $this->campaign->notifyCustomers($id);
                    if ($result['success']) {
                        return $this->jsonResponse([
                            'message' => 'Notifications sent successfully',
                            'data' => $result
                        ]);
                    }
                    return $this->jsonResponse(['error' => $result['error']], 500);
                } else {
                    // POST /api/campaigns - Create new campaign
                    if (!$this->validateCampaignData($data)) {
                        return $this->jsonResponse(['error' => 'Invalid campaign data'], 400);
                    }
                    
                    $campaignId = $this->campaign->create($data);
                    if ($campaignId) {
                        $campaign = $this->campaign->getById($campaignId);
                        return $this->jsonResponse([
                            'message' => 'Campaign created successfully',
                            'data' => $campaign
                        ], 201);
                    }
                    return $this->jsonResponse(['error' => 'Failed to create campaign'], 500);
                }
                
            default:
                return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Validate component data
     */
    private function validateComponentData($data) 
    {
        $required = ['component_type', 'component_name', 'model', 'warranty_period'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        $validTypes = ['battery', 'motor', 'bms', 'inverter', 'charger', 'controller', 'other'];
        if (!in_array($data['component_type'], $validTypes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate warranty policy data
     */
    private function validateWarrantyPolicyData($data) 
    {
        $required = ['component_id', 'policy_name', 'warranty_duration', 'effective_date'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate campaign data
     */
    private function validateCampaignData($data) 
    {
        $required = ['title', 'campaign_type', 'start_date'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        $validTypes = ['recall', 'service_campaign', 'maintenance'];
        if (!in_array($data['campaign_type'], $validTypes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Return JSON response
     */
    private function jsonResponse($data, $status = 200) 
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}