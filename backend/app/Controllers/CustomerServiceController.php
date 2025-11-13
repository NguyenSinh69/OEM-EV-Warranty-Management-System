<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Services\CustomerServiceIntegration;

class CustomerServiceController extends BaseController
{
    private Customer $customerModel;
    private Vehicle $vehicleModel;
    private CustomerServiceIntegration $integrationService;

    public function __construct()
    {
        $this->customerModel = new Customer();
        $this->vehicleModel = new Vehicle();
        $this->integrationService = new CustomerServiceIntegration();
    }

    public function syncCustomer(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['customer_code', 'action'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            $customerCode = $data['customer_code'];
            $action = $data['action']; // 'sync_from_cs' or 'sync_to_cs'
            
            if ($action === 'sync_from_cs') {
                // Fetch customer data from Customer Service system
                $customerData = $this->integrationService->fetchCustomerFromCS($customerCode);
                
                if (!$customerData) {
                    $response->error('Customer not found in Customer Service system', 404);
                    return;
                }
                
                // Check if customer already exists
                $existingCustomer = $this->customerModel->getCustomerByCode($customerCode);
                
                if ($existingCustomer) {
                    // Update existing customer
                    $customerId = $this->customerModel->update($existingCustomer['id'], $customerData);
                    $result = ['action' => 'updated', 'customer_id' => $existingCustomer['id']];
                } else {
                    // Create new customer
                    $customerId = $this->customerModel->create($customerData);
                    $result = ['action' => 'created', 'customer_id' => $customerId];
                }
                
            } elseif ($action === 'sync_to_cs') {
                // Send customer data to Customer Service system
                $customer = $this->customerModel->getCustomerByCode($customerCode);
                
                if (!$customer) {
                    $response->error('Customer not found in warranty system', 404);
                    return;
                }
                
                $syncResult = $this->integrationService->syncCustomerToCS($customer);
                $result = ['action' => 'synced_to_cs', 'success' => $syncResult];
                
            } else {
                $response->error('Invalid action. Use sync_from_cs or sync_to_cs', 422);
                return;
            }
            
            $this->logActivity('customer_sync', 'customer', $customer['id'] ?? null, [
                'action' => $action,
                'customer_code' => $customerCode
            ]);
            
            $response->success($result, 'Customer synchronization completed');
            
        } catch (\Exception $e) {
            $response->error('Failed to sync customer: ' . $e->getMessage(), 500);
        }
    }

    public function syncVehicle(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['vin', 'action'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            $vin = $data['vin'];
            $action = $data['action']; // 'sync_from_cs' or 'sync_to_cs'
            
            if ($action === 'sync_from_cs') {
                // Fetch vehicle data from Customer Service system
                $vehicleData = $this->integrationService->fetchVehicleFromCS($vin);
                
                if (!$vehicleData) {
                    $response->error('Vehicle not found in Customer Service system', 404);
                    return;
                }
                
                // Check if vehicle already exists
                $existingVehicle = $this->vehicleModel->findBy('vin', $vin);
                
                if ($existingVehicle) {
                    // Update existing vehicle
                    $vehicleId = $this->vehicleModel->update($existingVehicle['id'], $vehicleData);
                    $result = ['action' => 'updated', 'vehicle_id' => $existingVehicle['id']];
                } else {
                    // Create new vehicle
                    $vehicleId = $this->vehicleModel->create($vehicleData);
                    $result = ['action' => 'created', 'vehicle_id' => $vehicleId];
                }
                
            } elseif ($action === 'sync_to_cs') {
                // Send vehicle data to Customer Service system
                $vehicle = $this->vehicleModel->findBy('vin', $vin);
                
                if (!$vehicle) {
                    $response->error('Vehicle not found in warranty system', 404);
                    return;
                }
                
                $syncResult = $this->integrationService->syncVehicleToCS($vehicle);
                $result = ['action' => 'synced_to_cs', 'success' => $syncResult];
                
            } else {
                $response->error('Invalid action. Use sync_from_cs or sync_to_cs', 422);
                return;
            }
            
            $this->logActivity('vehicle_sync', 'vehicle', $vehicle['id'] ?? null, [
                'action' => $action,
                'vin' => $vin
            ]);
            
            $response->success($result, 'Vehicle synchronization completed');
            
        } catch (\Exception $e) {
            $response->error('Failed to sync vehicle: ' . $e->getMessage(), 500);
        }
    }

    public function getCustomerInfo(Request $request, Response $response): void
    {
        try {
            $customerCode = $request->getParam('customerCode');
            
            // Get customer info from both systems
            $warrantySystemData = $this->customerModel->getCustomerByCode($customerCode);
            $customerServiceData = $this->integrationService->fetchCustomerFromCS($customerCode);
            
            $result = [
                'customer_code' => $customerCode,
                'warranty_system' => $warrantySystemData,
                'customer_service' => $customerServiceData,
                'synchronized' => $this->compareCustomerData($warrantySystemData, $customerServiceData)
            ];
            
            if ($warrantySystemData) {
                // Get customer's vehicles and claims
                $result['vehicles'] = $this->customerModel->getCustomerVehicles($warrantySystemData['id']);
                $result['claims'] = $this->customerModel->getCustomerClaims($warrantySystemData['id']);
            }
            
            $response->success($result);
            
        } catch (\Exception $e) {
            $response->error('Failed to get customer info: ' . $e->getMessage(), 500);
        }
    }

    public function bulkSync(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            if (!isset($data['items']) || !is_array($data['items'])) {
                $response->error('items array is required', 422);
                return;
            }
            
            $results = [];
            $errors = [];
            
            foreach ($data['items'] as $item) {
                try {
                    if ($item['type'] === 'customer') {
                        $result = $this->processBulkCustomerSync($item);
                    } elseif ($item['type'] === 'vehicle') {
                        $result = $this->processBulkVehicleSync($item);
                    } else {
                        $errors[] = "Invalid type for item: {$item['type']}";
                        continue;
                    }
                    
                    $results[] = $result;
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to sync {$item['type']} {$item['identifier']}: " . $e->getMessage();
                }
            }
            
            $response->success([
                'processed' => count($results),
                'total' => count($data['items']),
                'results' => $results,
                'errors' => $errors
            ], 'Bulk synchronization completed');
            
        } catch (\Exception $e) {
            $response->error('Failed to process bulk sync: ' . $e->getMessage(), 500);
        }
    }

    public function getSyncStatus(Request $request, Response $response): void
    {
        try {
            // Get synchronization statistics
            $stats = $this->integrationService->getSyncStatistics();
            
            $response->success($stats);
            
        } catch (\Exception $e) {
            $response->error('Failed to get sync status: ' . $e->getMessage(), 500);
        }
    }

    private function compareCustomerData(?array $warrantyData, ?array $csData): bool
    {
        if (!$warrantyData || !$csData) {
            return false;
        }
        
        $keyFields = ['company_name', 'email', 'phone', 'address'];
        
        foreach ($keyFields as $field) {
            if (($warrantyData[$field] ?? '') !== ($csData[$field] ?? '')) {
                return false;
            }
        }
        
        return true;
    }

    private function processBulkCustomerSync(array $item): array
    {
        $customerCode = $item['identifier'];
        $action = $item['action'] ?? 'sync_from_cs';
        
        if ($action === 'sync_from_cs') {
            $customerData = $this->integrationService->fetchCustomerFromCS($customerCode);
            
            if (!$customerData) {
                throw new \Exception('Customer not found in Customer Service system');
            }
            
            $existingCustomer = $this->customerModel->getCustomerByCode($customerCode);
            
            if ($existingCustomer) {
                $this->customerModel->update($existingCustomer['id'], $customerData);
                return ['type' => 'customer', 'identifier' => $customerCode, 'action' => 'updated'];
            } else {
                $customerId = $this->customerModel->create($customerData);
                return ['type' => 'customer', 'identifier' => $customerCode, 'action' => 'created', 'id' => $customerId];
            }
        }
        
        throw new \Exception('Invalid action for bulk customer sync');
    }

    private function processBulkVehicleSync(array $item): array
    {
        $vin = $item['identifier'];
        $action = $item['action'] ?? 'sync_from_cs';
        
        if ($action === 'sync_from_cs') {
            $vehicleData = $this->integrationService->fetchVehicleFromCS($vin);
            
            if (!$vehicleData) {
                throw new \Exception('Vehicle not found in Customer Service system');
            }
            
            $existingVehicle = $this->vehicleModel->findBy('vin', $vin);
            
            if ($existingVehicle) {
                $this->vehicleModel->update($existingVehicle['id'], $vehicleData);
                return ['type' => 'vehicle', 'identifier' => $vin, 'action' => 'updated'];
            } else {
                $vehicleId = $this->vehicleModel->create($vehicleData);
                return ['type' => 'vehicle', 'identifier' => $vin, 'action' => 'created', 'id' => $vehicleId];
            }
        }
        
        throw new \Exception('Invalid action for bulk vehicle sync');
    }
}