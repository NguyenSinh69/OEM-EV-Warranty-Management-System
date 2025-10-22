<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles
     */
    public function index(Request $request): JsonResponse
    {
        // Mock vehicle data
        $vehicles = [
            [
                'id' => 1,
                'vin' => 'VF3ABCDEF12345678',
                'model' => 'VinFast VF8',
                'year' => 2024,
                'color' => 'Đen Kim Cương',
                'customer_id' => 1,
                'purchase_date' => '2024-01-15',
                'warranty_start_date' => '2024-01-15',
                'warranty_end_date' => '2026-01-15',
                'status' => 'active',
                'mileage' => 5000,
                'battery_capacity' => '87.7 kWh',
                'motor_power' => '300 kW'
            ],
            [
                'id' => 2,
                'vin' => 'VF3GHIJKL87654321',
                'model' => 'VinFast VF9',
                'year' => 2024,
                'color' => 'Trắng Ngọc Trai',
                'customer_id' => 2,
                'purchase_date' => '2024-02-20',
                'warranty_start_date' => '2024-02-20',
                'warranty_end_date' => '2026-02-20',
                'status' => 'active',
                'mileage' => 3000,
                'battery_capacity' => '123 kWh',
                'motor_power' => '300 kW'
            ]
        ];

        // Apply filters
        if ($request->customer_id) {
            $vehicles = array_filter($vehicles, function($vehicle) use ($request) {
                return $vehicle['customer_id'] == $request->customer_id;
            });
        }

        if ($request->search) {
            $search = strtolower($request->search);
            $vehicles = array_filter($vehicles, function($vehicle) use ($search) {
                return strpos(strtolower($vehicle['vin']), $search) !== false ||
                       strpos(strtolower($vehicle['model']), $search) !== false;
            });
        }

        return response()->json([
            'success' => true,
            'data' => array_values($vehicles),
            'message' => 'Vehicles retrieved successfully'
        ]);
    }

    /**
     * Store a newly created vehicle
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vin' => 'required|string|size:17|unique:vehicles',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:2020|max:2030',
            'color' => 'required|string|max:50',
            'customer_id' => 'required|integer',
            'purchase_date' => 'required|date',
            'warranty_start_date' => 'required|date',
            'warranty_end_date' => 'required|date|after:warranty_start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mock vehicle creation
        $vehicle = array_merge($request->all(), [
            'id' => rand(1000, 9999),
            'status' => 'active',
            'mileage' => 0,
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle registered successfully'
        ], 201);
    }

    /**
     * Display the specified vehicle
     */
    public function show($vin): JsonResponse
    {
        // Mock vehicle lookup
        $vehicle = [
            'id' => 1,
            'vin' => $vin,
            'model' => 'VinFast VF8',
            'year' => 2024,
            'color' => 'Đen Kim Cương',
            'customer_id' => 1,
            'purchase_date' => '2024-01-15',
            'warranty_start_date' => '2024-01-15',
            'warranty_end_date' => '2026-01-15',
            'status' => 'active',
            'mileage' => 5000,
            'battery_capacity' => '87.7 kWh',
            'motor_power' => '300 kW',
            'specifications' => [
                'range' => '425 km',
                'top_speed' => '200 km/h',
                'acceleration' => '5.5s (0-100km/h)',
                'charging_time' => '35 phút (10-80%)',
                'drive_type' => 'AWD'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle retrieved successfully'
        ]);
    }

    /**
     * Update the specified vehicle
     */
    public function update(Request $request, $vin): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'sometimes|required|string|max:100',
            'color' => 'sometimes|required|string|max:50',
            'status' => 'sometimes|required|in:active,inactive,maintenance,sold',
            'mileage' => 'sometimes|required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Mock vehicle update
        $vehicle = [
            'id' => 1,
            'vin' => $vin,
            'model' => $request->get('model', 'VinFast VF8'),
            'year' => 2024,
            'color' => $request->get('color', 'Đen Kim Cương'),
            'status' => $request->get('status', 'active'),
            'mileage' => $request->get('mileage', 5000),
            'updated_at' => now()
        ];

        return response()->json([
            'success' => true,
            'data' => $vehicle,
            'message' => 'Vehicle updated successfully'
        ]);
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