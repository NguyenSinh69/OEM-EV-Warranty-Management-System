<?php

namespace App\Services;

use App\Core\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CustomerServiceIntegration
{
    private Client $httpClient;
    private Database $db;
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => false // For development only
        ]);
        
        $this->db = Database::getInstance();
        $this->apiUrl = $_ENV['CUSTOMER_SERVICE_API_URL'] ?? 'http://localhost:8082/api';
        $this->apiKey = $_ENV['CUSTOMER_SERVICE_API_KEY'] ?? 'default-api-key';
    }

    public function fetchCustomerFromCS(string $customerCode): ?array
    {
        try {
            $response = $this->httpClient->get("{$this->apiUrl}/customers/{$customerCode}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data && isset($data['success']) && $data['success']) {
                return $this->transformCustomerDataFromCS($data['data']);
            }
            
            return null;
            
        } catch (RequestException $e) {
            error_log("Failed to fetch customer from CS: " . $e->getMessage());
            
            // Log the integration attempt
            $this->logIntegrationAttempt('fetch_customer', $customerCode, 'failed', $e->getMessage());
            
            return null;
        }
    }

    public function fetchVehicleFromCS(string $vin): ?array
    {
        try {
            $response = $this->httpClient->get("{$this->apiUrl}/vehicles/vin/{$vin}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data && isset($data['success']) && $data['success']) {
                return $this->transformVehicleDataFromCS($data['data']);
            }
            
            return null;
            
        } catch (RequestException $e) {
            error_log("Failed to fetch vehicle from CS: " . $e->getMessage());
            
            // Log the integration attempt
            $this->logIntegrationAttempt('fetch_vehicle', $vin, 'failed', $e->getMessage());
            
            return null;
        }
    }

    public function syncCustomerToCS(array $customer): bool
    {
        try {
            $csData = $this->transformCustomerDataToCS($customer);
            
            $response = $this->httpClient->post("{$this->apiUrl}/customers/sync", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => $csData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            $success = $data && isset($data['success']) && $data['success'];
            
            // Log the integration attempt
            $this->logIntegrationAttempt(
                'sync_customer_to_cs', 
                $customer['customer_code'], 
                $success ? 'success' : 'failed',
                $success ? null : ($data['message'] ?? 'Unknown error')
            );
            
            return $success;
            
        } catch (RequestException $e) {
            error_log("Failed to sync customer to CS: " . $e->getMessage());
            
            $this->logIntegrationAttempt('sync_customer_to_cs', $customer['customer_code'], 'failed', $e->getMessage());
            
            return false;
        }
    }

    public function syncVehicleToCS(array $vehicle): bool
    {
        try {
            $csData = $this->transformVehicleDataToCS($vehicle);
            
            $response = $this->httpClient->post("{$this->apiUrl}/vehicles/sync", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => $csData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            $success = $data && isset($data['success']) && $data['success'];
            
            // Log the integration attempt
            $this->logIntegrationAttempt(
                'sync_vehicle_to_cs', 
                $vehicle['vin'], 
                $success ? 'success' : 'failed',
                $success ? null : ($data['message'] ?? 'Unknown error')
            );
            
            return $success;
            
        } catch (RequestException $e) {
            error_log("Failed to sync vehicle to CS: " . $e->getMessage());
            
            $this->logIntegrationAttempt('sync_vehicle_to_cs', $vehicle['vin'], 'failed', $e->getMessage());
            
            return false;
        }
    }

    public function syncWarrantyClaimToCS(array $claim): bool
    {
        try {
            $csData = $this->transformClaimDataToCS($claim);
            
            $response = $this->httpClient->post("{$this->apiUrl}/warranty-claims/sync", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => $csData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            $success = $data && isset($data['success']) && $data['success'];
            
            // Log the integration attempt
            $this->logIntegrationAttempt(
                'sync_claim_to_cs', 
                $claim['claim_number'], 
                $success ? 'success' : 'failed',
                $success ? null : ($data['message'] ?? 'Unknown error')
            );
            
            return $success;
            
        } catch (RequestException $e) {
            error_log("Failed to sync claim to CS: " . $e->getMessage());
            
            $this->logIntegrationAttempt('sync_claim_to_cs', $claim['claim_number'], 'failed', $e->getMessage());
            
            return false;
        }
    }

    public function notifyCustomerServiceClaimUpdate(string $claimNumber, string $status, array $details = []): bool
    {
        try {
            $payload = [
                'claim_number' => $claimNumber,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
                'details' => $details
            ];
            
            $response = $this->httpClient->post("{$this->apiUrl}/warranty-claims/status-update", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data && isset($data['success']) && $data['success'];
            
        } catch (RequestException $e) {
            error_log("Failed to notify CS of claim update: " . $e->getMessage());
            return false;
        }
    }

    public function getSyncStatistics(): array
    {
        $sql = "
            SELECT 
                action,
                status,
                COUNT(*) as count,
                MAX(created_at) as last_attempt
            FROM integration_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY action, status
            ORDER BY action, status
        ";
        
        $logs = $this->db->fetchAll($sql);
        
        $stats = [
            'last_30_days' => [],
            'summary' => [
                'total_attempts' => 0,
                'successful' => 0,
                'failed' => 0,
                'success_rate' => 0
            ]
        ];
        
        foreach ($logs as $log) {
            $stats['last_30_days'][] = $log;
            $stats['summary']['total_attempts'] += (int)$log['count'];
            
            if ($log['status'] === 'success') {
                $stats['summary']['successful'] += (int)$log['count'];
            } else {
                $stats['summary']['failed'] += (int)$log['count'];
            }
        }
        
        if ($stats['summary']['total_attempts'] > 0) {
            $stats['summary']['success_rate'] = round(
                ($stats['summary']['successful'] / $stats['summary']['total_attempts']) * 100, 
                2
            );
        }
        
        return $stats;
    }

    private function transformCustomerDataFromCS(array $csData): array
    {
        return [
            'customer_code' => $csData['customer_code'] ?? '',
            'company_name' => $csData['company_name'] ?? '',
            'address' => $csData['address'] ?? '',
            'city' => $csData['city'] ?? '',
            'state' => $csData['state'] ?? '',
            'postal_code' => $csData['postal_code'] ?? '',
            'country' => $csData['country'] ?? 'Vietnam',
            'tax_id' => $csData['tax_id'] ?? '',
            'contact_person' => $csData['contact_person'] ?? '',
            'user_id' => null, // Will be set separately if user exists
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function transformVehicleDataFromCS(array $csData): array
    {
        return [
            'vin' => $csData['vin'] ?? '',
            'make' => $csData['make'] ?? '',
            'model' => $csData['model'] ?? '',
            'year' => $csData['year'] ?? date('Y'),
            'color' => $csData['color'] ?? '',
            'battery_capacity' => $csData['battery_capacity'] ?? null,
            'motor_power' => $csData['motor_power'] ?? null,
            'manufacturing_date' => $csData['manufacturing_date'] ?? null,
            'delivery_date' => $csData['delivery_date'] ?? null,
            'mileage' => $csData['mileage'] ?? 0,
            'status' => $csData['status'] ?? 'active',
            'customer_id' => null, // Will be resolved separately
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function transformCustomerDataToCS(array $warrantyData): array
    {
        return [
            'customer_code' => $warrantyData['customer_code'],
            'company_name' => $warrantyData['company_name'],
            'address' => $warrantyData['address'],
            'city' => $warrantyData['city'],
            'state' => $warrantyData['state'],
            'postal_code' => $warrantyData['postal_code'],
            'country' => $warrantyData['country'],
            'tax_id' => $warrantyData['tax_id'],
            'contact_person' => $warrantyData['contact_person'],
            'email' => $warrantyData['email'] ?? '',
            'phone' => $warrantyData['phone'] ?? '',
            'source' => 'warranty_system',
            'last_updated' => $warrantyData['updated_at']
        ];
    }

    private function transformVehicleDataToCS(array $warrantyData): array
    {
        return [
            'vin' => $warrantyData['vin'],
            'make' => $warrantyData['make'],
            'model' => $warrantyData['model'],
            'year' => $warrantyData['year'],
            'color' => $warrantyData['color'],
            'battery_capacity' => $warrantyData['battery_capacity'],
            'motor_power' => $warrantyData['motor_power'],
            'manufacturing_date' => $warrantyData['manufacturing_date'],
            'delivery_date' => $warrantyData['delivery_date'],
            'mileage' => $warrantyData['mileage'],
            'status' => $warrantyData['status'],
            'customer_code' => $warrantyData['customer_code'] ?? '',
            'source' => 'warranty_system',
            'last_updated' => $warrantyData['updated_at']
        ];
    }

    private function transformClaimDataToCS(array $claimData): array
    {
        return [
            'claim_number' => $claimData['claim_number'],
            'vin' => $claimData['vin'] ?? '',
            'customer_code' => $claimData['customer_code'] ?? '',
            'claim_type' => $claimData['claim_type'],
            'priority' => $claimData['priority'],
            'status' => $claimData['status'],
            'issue_description' => $claimData['issue_description'],
            'symptoms' => $claimData['symptoms'],
            'estimated_cost' => $claimData['estimated_cost'],
            'approved_amount' => $claimData['approved_amount'],
            'incident_date' => $claimData['incident_date'],
            'reported_date' => $claimData['reported_date'],
            'source' => 'warranty_system',
            'last_updated' => $claimData['updated_at']
        ];
    }

    private function logIntegrationAttempt(string $action, string $identifier, string $status, ?string $error = null): void
    {
        try {
            $this->db->insert('integration_logs', [
                'action' => $action,
                'identifier' => $identifier,
                'status' => $status,
                'error_message' => $error,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Failed to log integration attempt: " . $e->getMessage());
        }
    }
}