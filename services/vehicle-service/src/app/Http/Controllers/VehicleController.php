<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function __construct()
    {
        // Initialize mock data on first load
        Vehicle::initializeMockData();
    }

    /**
     * Display a listing of vehicles
     * GET /vehicles or GET /vehicles?customer_id=xxx
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $vehicles = [];
            
            if ($request->has('customer_id')) {
                $vehicles = Vehicle::findByCustomerId($request->get('customer_id'));
            } else {
                $vehicles = Vehicle::all();
            }
            
            // Convert to array format
            $vehicleData = array_map(function($vehicle) {
                return $vehicle->toArray();
            }, $vehicles);

            return response()->json([
                'success' => true,
                'data' => $vehicleData,
                'message' => 'Vehicles retrieved successfully',
                'timestamp' => date('c')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VEHICLE_RETRIEVAL_ERROR',
                    'message' => $e->getMessage(),
                    'details' => []
                ],
                'timestamp' => date('c')
            ], 500);
        }
    }

    /**
     * Store a newly created vehicle
     * POST /vehicles
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Basic validation
            if (empty($data['vin'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'VIN is required',
                        'details' => ['vin' => 'VIN field is required']
                    ],
                    'timestamp' => date('c')
                ], 400);
            }
            
            if (empty($data['model'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Model is required',
                        'details' => ['model' => 'Model field is required']
                    ],
                    'timestamp' => date('c')
                ], 400);
            }
            
            if (empty($data['year']) || !is_numeric($data['year'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Year is required and must be numeric',
                        'details' => ['year' => 'Year field is required and must be numeric']
                    ],
                    'timestamp' => date('c')
                ], 400);
            }
            
            $vehicle = Vehicle::create($data);
            
            return response()->json([
                'success' => true,
                'data' => $vehicle->toArray(),
                'message' => 'Vehicle created successfully',
                'timestamp' => date('c')
            ], 201);
            
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            $errorCode = 'VEHICLE_CREATION_ERROR';
            
            if ($statusCode == 400) {
                $errorCode = 'VALIDATION_ERROR';
            } elseif ($statusCode == 409) {
                $errorCode = 'VEHICLE_EXISTS';
            }
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $e->getMessage(),
                    'details' => []
                ],
                'timestamp' => date('c')
            ], $statusCode);
        }
    }

    /**
     * Display the specified vehicle
     * GET /vehicles/{vin}
     */
    public function show($vin): JsonResponse
    {
        try {
            $vehicle = Vehicle::findByVin($vin);
            
            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VEHICLE_NOT_FOUND',
                        'message' => 'Vehicle not found',
                        'details' => ['vin' => 'Vehicle with this VIN does not exist']
                    ],
                    'timestamp' => date('c')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $vehicle->toArray(),
                'message' => 'Vehicle retrieved successfully',
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VEHICLE_RETRIEVAL_ERROR',
                    'message' => $e->getMessage(),
                    'details' => []
                ],
                'timestamp' => date('c')
            ], 500);
        }
    }

    /**
     * Update the specified vehicle
     * PUT /vehicles/{vin}
     */
    public function update(Request $request, $vin): JsonResponse
    {
        try {
            $vehicle = Vehicle::findByVin($vin);
            
            if (!$vehicle) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VEHICLE_NOT_FOUND',
                        'message' => 'Vehicle not found',
                        'details' => ['vin' => 'Vehicle with this VIN does not exist']
                    ],
                    'timestamp' => date('c')
                ], 404);
            }
            
            $data = $request->all();
            $vehicle->update($data);

            return response()->json([
                'success' => true,
                'data' => $vehicle->toArray(),
                'message' => 'Vehicle updated successfully',
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            $errorCode = 'VEHICLE_UPDATE_ERROR';
            
            if ($statusCode == 400) {
                $errorCode = 'VALIDATION_ERROR';
            }
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $e->getMessage(),
                    'details' => []
                ],
                'timestamp' => date('c')
            ], $statusCode);
        }
    }

    /**
     * Get vehicle warranty information
     */
    public function getWarranty($vin): JsonResponse
    {
        $warranty = [
            'vin' => $vin,
            'warranty_active' => true,
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2026-01-15',
            'remaining_days' => 400,
            'coverage' => [
                'battery' => [
                    'covered' => true,
                    'duration' => '8 năm hoặc 160,000 km'
                ],
                'motor' => [
                    'covered' => true,
                    'duration' => '3 năm hoặc 100,000 km'
                ],
                'electrical' => [
                    'covered' => true,
                    'duration' => '2 năm hoặc 50,000 km'
                ],
                'software' => [
                    'covered' => true,
                    'duration' => '2 năm'
                ]
            ],
            'claim_history' => [
                [
                    'claim_number' => 'WC-2024-000001',
                    'date' => '2024-03-15',
                    'issue' => 'Battery charging issue',
                    'status' => 'completed',
                    'cost' => 0
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $warranty,
            'message' => 'Vehicle warranty information retrieved successfully'
        ]);
    }

    /**
     * Get vehicle service history
     */
    public function getServiceHistory($vin): JsonResponse
    {
        $serviceHistory = [
            [
                'id' => 1,
                'date' => '2024-06-15',
                'type' => 'maintenance',
                'description' => 'Định kỳ 10,000 km',
                'service_center' => 'VinFast Hà Nội',
                'cost' => 500000,
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'date' => '2024-03-15',
                'type' => 'warranty',
                'description' => 'Sửa lỗi sạc pin',
                'service_center' => 'VinFast Hà Nội',
                'cost' => 0,
                'status' => 'completed'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $serviceHistory,
            'message' => 'Vehicle service history retrieved successfully'
        ]);
    }

    /**
     * Vehicle lookup for other services
     */
    public function lookup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vin' => 'required|string|size:17',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'VIN is required',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mock vehicle lookup
        $vehicle = [
            'vin' => $request->vin,
            'exists' => true,
            'customer_id' => 1,
            'model' => 'VinFast VF8',
            'warranty_active' => true,
            'warranty_end_date' => '2026-01-15'
        ];

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle lookup completed'
        ]);
    }
}