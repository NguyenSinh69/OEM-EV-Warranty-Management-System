<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\VehicleWarranty;
use App\Models\Vehicle;

class VehicleWarrantyController extends BaseController
{
    private VehicleWarranty $warrantyModel;
    private Vehicle $vehicleModel;

    public function __construct()
    {
        $this->warrantyModel = new VehicleWarranty();
        $this->vehicleModel = new Vehicle();
    }

    public function index(Request $request, Response $response): void
    {
        try {
            $page = (int)($request->getQuery('page') ?? 1);
            $perPage = (int)($request->getQuery('per_page') ?? 10);
            $status = $request->getQuery('status');
            
            $conditions = [];
            if ($status) {
                $conditions['status'] = $status;
            }
            
            $result = $this->warrantyModel->paginate($page, $perPage, $conditions);
            
            $response->paginated(
                $result['data'],
                $result['total'],
                $result['page'],
                $result['per_page']
            );
        } catch (\Exception $e) {
            $response->error('Failed to fetch warranties: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $warranty = $this->warrantyModel->find($id);
            
            if (!$warranty) {
                $response->error('Warranty not found', 404);
                return;
            }
            
            $response->success($warranty);
        } catch (\Exception $e) {
            $response->error('Failed to fetch warranty: ' . $e->getMessage(), 500);
        }
    }

    public function getByVehicle(Request $request, Response $response): void
    {
        try {
            $vehicleId = (int)$request->getParam('vehicleId');
            
            $vehicle = $this->vehicleModel->find($vehicleId);
            if (!$vehicle) {
                $response->error('Vehicle not found', 404);
                return;
            }
            
            $warranties = $this->warrantyModel->where(['vehicle_id' => $vehicleId]);
            
            $response->success($warranties);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch vehicle warranties: ' . $e->getMessage(), 500);
        }
    }

    public function getByVin(Request $request, Response $response): void
    {
        try {
            $vin = $request->getParam('vin');
            
            $warranties = $this->warrantyModel->getWarrantiesByVin($vin);
            
            $response->success($warranties);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch warranties by VIN: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['vehicle_id', 'policy_id', 'start_date', 'end_date'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            // Validate dates
            if (!$this->validateDate($data['start_date']) || !$this->validateDate($data['end_date'])) {
                $response->error('Invalid date format. Use YYYY-MM-DD', 422);
                return;
            }
            
            // Generate warranty number if not provided
            if (!isset($data['warranty_number'])) {
                $data['warranty_number'] = $this->warrantyModel->generateWarrantyNumber();
            }
            
            $data['status'] = 'active';
            
            $warrantyId = $this->warrantyModel->create($data);
            
            $this->logActivity('create', 'vehicle_warranty', (int)$warrantyId, [
                'warranty_number' => $data['warranty_number'],
                'vehicle_id' => $data['vehicle_id']
            ]);
            
            $response->success([
                'id' => $warrantyId,
                'warranty_number' => $data['warranty_number']
            ], 'Vehicle warranty created successfully');
            
        } catch (\Exception $e) {
            $response->error('Failed to create warranty: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $data = $this->sanitizeInput($request->getBody());
            
            $warranty = $this->warrantyModel->find($id);
            if (!$warranty) {
                $response->error('Warranty not found', 404);
                return;
            }
            
            $updated = $this->warrantyModel->update($id, $data);
            
            if ($updated) {
                $this->logActivity('update', 'vehicle_warranty', $id, $data);
                $response->success(['updated' => true], 'Warranty updated successfully');
            } else {
                $response->error('Failed to update warranty', 500);
            }
            
        } catch (\Exception $e) {
            $response->error('Failed to update warranty: ' . $e->getMessage(), 500);
        }
    }
}