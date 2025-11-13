<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\WarrantyClaim;
use App\Models\VehicleWarranty;
use App\Services\NotificationService;

class WarrantyClaimController extends BaseController
{
    private WarrantyClaim $warrantyClaimModel;
    private VehicleWarranty $vehicleWarrantyModel;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->warrantyClaimModel = new WarrantyClaim();
        $this->vehicleWarrantyModel = new VehicleWarranty();
        $this->notificationService = new NotificationService();
    }

    public function index(Request $request, Response $response): void
    {
        try {
            $page = (int)($request->getQuery('page') ?? 1);
            $perPage = (int)($request->getQuery('per_page') ?? 10);
            $status = $request->getQuery('status');
            $customerId = $request->getQuery('customer_id');
            
            $conditions = [];
            if ($status) {
                $conditions['status'] = $status;
            }
            if ($customerId) {
                $conditions['customer_id'] = $customerId;
            }
            
            $result = $this->warrantyClaimModel->paginate($page, $perPage, $conditions);
            
            $response->paginated(
                $result['data'],
                $result['total'],
                $result['page'],
                $result['per_page']
            );
        } catch (\Exception $e) {
            $response->error('Failed to fetch warranty claims: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $claim = $this->warrantyClaimModel->getWithDetails($id);
            
            if (!$claim) {
                $response->error('Warranty claim not found', 404);
                return;
            }
            
            $response->success($claim);
        } catch (\Exception $e) {
            $response->error('Failed to fetch warranty claim: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = [
                'vehicle_warranty_id',
                'customer_id',
                'claim_type',
                'issue_description',
                'incident_date'
            ];
            
            $errors = $this->validateRequired($data, $required);
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            // Validate warranty exists and is active
            $warranty = $this->vehicleWarrantyModel->find($data['vehicle_warranty_id']);
            if (!$warranty) {
                $response->error('Vehicle warranty not found', 404);
                return;
            }
            
            // Check warranty validity
            $validityCheck = $this->vehicleWarrantyModel->checkWarrantyValidity(
                $warranty['vehicle_id'],
                $data['incident_date']
            );
            
            if (empty($validityCheck) || $validityCheck[0]['validity_status'] !== 'valid') {
                $response->error('Warranty is not valid for this claim', 422);
                return;
            }
            
            // Generate claim number
            $data['claim_number'] = $this->warrantyClaimModel->generateClaimNumber();
            $data['status'] = 'draft';
            $data['created_by'] = $this->getCurrentUserId($request);
            $data['reported_date'] = date('Y-m-d H:i:s');
            
            $claimId = $this->warrantyClaimModel->create($data);
            
            // Log activity
            $this->logActivity('create', 'warranty_claim', (int)$claimId, [
                'claim_number' => $data['claim_number'],
                'claim_type' => $data['claim_type']
            ]);
            
            // Send notification
            $this->notificationService->sendClaimCreatedNotification((int)$claimId);
            
            $response->success([
                'id' => $claimId,
                'claim_number' => $data['claim_number']
            ], 'Warranty claim created successfully');
            
        } catch (\Exception $e) {
            $response->error('Failed to create warranty claim: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $data = $this->sanitizeInput($request->getBody());
            
            $claim = $this->warrantyClaimModel->find($id);
            if (!$claim) {
                $response->error('Warranty claim not found', 404);
                return;
            }
            
            // Check if claim can be updated
            if (in_array($claim['status'], ['completed', 'cancelled'])) {
                $response->error('Cannot update completed or cancelled claims', 422);
                return;
            }
            
            $updated = $this->warrantyClaimModel->update($id, $data);
            
            if ($updated) {
                $this->logActivity('update', 'warranty_claim', $id, $data);
                $response->success(['updated' => true], 'Warranty claim updated successfully');
            } else {
                $response->error('Failed to update warranty claim', 500);
            }
            
        } catch (\Exception $e) {
            $response->error('Failed to update warranty claim: ' . $e->getMessage(), 500);
        }
    }

    public function updateStatus(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $data = $this->sanitizeInput($request->getBody());
            
            if (!isset($data['status'])) {
                $response->error('Status is required', 422);
                return;
            }
            
            $claim = $this->warrantyClaimModel->find($id);
            if (!$claim) {
                $response->error('Warranty claim not found', 404);
                return;
            }
            
            $validStatuses = [
                'draft', 'submitted', 'under_review', 'investigating', 
                'approved', 'rejected', 'in_progress', 'completed', 'cancelled'
            ];
            
            if (!in_array($data['status'], $validStatuses)) {
                $response->error('Invalid status', 422);
                return;
            }
            
            $updated = $this->warrantyClaimModel->updateStatus(
                $id,
                $data['status'],
                $data['notes'] ?? null
            );
            
            if ($updated) {
                $this->logActivity('status_change', 'warranty_claim', $id, [
                    'old_status' => $claim['status'],
                    'new_status' => $data['status'],
                    'notes' => $data['notes'] ?? null
                ]);
                
                // Send status change notification
                $this->notificationService->sendClaimStatusChangeNotification($id, $data['status']);
                
                $response->success(['updated' => true], 'Claim status updated successfully');
            } else {
                $response->error('Failed to update claim status', 500);
            }
            
        } catch (\Exception $e) {
            $response->error('Failed to update claim status: ' . $e->getMessage(), 500);
        }
    }

    public function getStatistics(Request $request, Response $response): void
    {
        try {
            $statistics = $this->warrantyClaimModel->getClaimStatistics();
            $response->success($statistics);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch statistics: ' . $e->getMessage(), 500);
        }
    }

    public function getApprovalQueue(Request $request, Response $response): void
    {
        try {
            $claims = $this->warrantyClaimModel->getClaimsRequiringApproval();
            $response->success($claims);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch approval queue: ' . $e->getMessage(), 500);
        }
    }

    public function delete(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            
            $claim = $this->warrantyClaimModel->find($id);
            if (!$claim) {
                $response->error('Warranty claim not found', 404);
                return;
            }
            
            // Only allow deletion of draft claims
            if ($claim['status'] !== 'draft') {
                $response->error('Only draft claims can be deleted', 422);
                return;
            }
            
            $deleted = $this->warrantyClaimModel->delete($id);
            
            if ($deleted) {
                $this->logActivity('delete', 'warranty_claim', $id, [
                    'claim_number' => $claim['claim_number']
                ]);
                
                $response->success(['deleted' => true], 'Warranty claim deleted successfully');
            } else {
                $response->error('Failed to delete warranty claim', 500);
            }
            
        } catch (\Exception $e) {
            $response->error('Failed to delete warranty claim: ' . $e->getMessage(), 500);
        }
    }
}