<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Vehicle;

class VehicleController extends BaseController
{
    protected $vehicleModel;

    public function __construct()
    {
        $this->vehicleModel = new Vehicle();
    }

    public function index(Request $request, Response $response)
    {
        try {
            $page = (int) $request->getQuery('page', 1);
            $perPage = (int) $request->getQuery('per_page', 10);
            $customerId = $request->getQuery('customer_id');
            $make = $request->getQuery('make');
            $model = $request->getQuery('model');
            $year = $request->getQuery('year');
            $status = $request->getQuery('status');

            $filters = array_filter([
                'customer_id' => $customerId,
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'status' => $status
            ]);

            $result = $this->vehicleModel->paginate($page, $perPage, $filters);

            return $response->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'per_page' => $result['per_page'],
                    'total_pages' => ceil($result['total'] / $result['per_page']),
                    'has_next_page' => $result['page'] < ceil($result['total'] / $result['per_page']),
                    'has_prev_page' => $result['page'] > 1
                ]
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Response $response)
    {
        try {
            $id = $request->getPathParam('id');
            $vehicle = $this->vehicleModel->find($id);

            if (!$vehicle) {
                return $response->json([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ], 404);
            }

            return $response->json([
                'success' => true,
                'data' => $vehicle
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByVin(Request $request, Response $response)
    {
        try {
            $vin = $request->getPathParam('vin');
            $vehicle = $this->vehicleModel->findBy('vin', $vin);

            if (!$vehicle) {
                return $response->json([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ], 404);
            }

            return $response->json([
                'success' => true,
                'data' => $vehicle
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, Response $response)
    {
        try {
            $data = $request->getJsonBody();

            $requiredFields = ['customer_id', 'vin', 'make', 'model', 'year'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $response->json([
                    'success' => false,
                    'message' => 'Missing required fields',
                    'missing_fields' => $missingFields
                ], 422);
            }

            // VIN validation
            if (strlen($data['vin']) !== 17) {
                return $response->json([
                    'success' => false,
                    'message' => 'Invalid VIN format. VIN must be 17 characters'
                ], 422);
            }

            // Check if VIN already exists
            $existingVehicle = $this->vehicleModel->findBy('vin', $data['vin']);
            if ($existingVehicle) {
                return $response->json([
                    'success' => false,
                    'message' => 'Vehicle with this VIN already exists'
                ], 409);
            }

            $vehicleId = $this->vehicleModel->create($data);

            return $response->json([
                'success' => true,
                'message' => 'Vehicle created successfully',
                'data' => ['id' => $vehicleId]
            ], 201);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to create vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Response $response)
    {
        try {
            $id = $request->getPathParam('id');
            $data = $request->getJsonBody();

            $vehicle = $this->vehicleModel->find($id);
            if (!$vehicle) {
                return $response->json([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ], 404);
            }

            // If VIN is being updated, check for duplicates
            if (isset($data['vin']) && $data['vin'] !== $vehicle['vin']) {
                if (strlen($data['vin']) !== 17) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Invalid VIN format. VIN must be 17 characters'
                    ], 422);
                }

                $existingVehicle = $this->vehicleModel->findBy('vin', $data['vin']);
                if ($existingVehicle) {
                    return $response->json([
                        'success' => false,
                        'message' => 'Vehicle with this VIN already exists'
                    ], 409);
                }
            }

            $this->vehicleModel->update($id, $data);

            return $response->json([
                'success' => true,
                'message' => 'Vehicle updated successfully'
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to update vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request, Response $response)
    {
        try {
            $id = $request->getPathParam('id');

            $vehicle = $this->vehicleModel->find($id);
            if (!$vehicle) {
                return $response->json([
                    'success' => false,
                    'message' => 'Vehicle not found'
                ], 404);
            }

            // Check if vehicle has active warranty claims
            $db = $this->vehicleModel->db;
            $stmt = $db->prepare("
                SELECT COUNT(*) as claim_count 
                FROM warranty_claims 
                WHERE vehicle_id = ? AND status IN ('pending', 'under_review', 'approved')
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch();

            if ($result['claim_count'] > 0) {
                return $response->json([
                    'success' => false,
                    'message' => 'Cannot delete vehicle with active warranty claims'
                ], 409);
            }

            $this->vehicleModel->delete($id);

            return $response->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully'
            ]);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Failed to delete vehicle',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}