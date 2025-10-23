<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\ServiceCenter;

class WarrantyController extends Controller
{
    /**
     * Display a listing of warranty claims
     */
    public function index(Request $request): JsonResponse
    {
        $warranties = WarrantyClaim::with(['customer', 'vehicle', 'serviceCenter'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                return $query->where('priority', $priority);
            })
            ->when($request->customer_id, function ($query, $customerId) {
                return $query->where('customer_id', $customerId);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('claim_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('vehicle_vin', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $warranties,
            'message' => 'Warranty claims retrieved successfully'
        ]);
    }

    /**
     * Store a newly created warranty claim
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'vehicle_vin' => 'required|string|max:17',
            'description' => 'required|string|max:1000',
            'issue_type' => 'required|string|in:electrical,mechanical,software,battery,other',
            'priority' => 'required|string|in:low,medium,high,critical',
            'service_center_id' => 'sometimes|integer|exists:service_centers,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify customer exists
        $customerExists = $this->verifyCustomer($request->customer_id);
        if (!$customerExists) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // Verify vehicle warranty status
        $vehicleWarranty = $this->checkVehicleWarranty($request->vehicle_vin);
        if (!$vehicleWarranty['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle warranty is not valid or expired'
            ], 400);
        }

        // Generate claim number
        $claimNumber = 'WC-' . date('Y') . '-' . str_pad(WarrantyClaim::count() + 1, 6, '0', STR_PAD_LEFT);

        $warrantyClaim = WarrantyClaim::create([
            'claim_number' => $claimNumber,
            'customer_id' => $request->customer_id,
            'vehicle_vin' => $request->vehicle_vin,
            'description' => $request->description,
            'issue_type' => $request->issue_type,
            'priority' => $request->priority,
            'status' => 'pending',
            'service_center_id' => $request->service_center_id,
            'estimated_cost' => 0,
            'created_by' => $request->user_id ?? null
        ]);

        // Send notification to customer
        $this->sendNotification($request->customer_id, 'warranty_claim_created', [
            'claim_number' => $claimNumber,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'data' => $warrantyClaim->load(['customer', 'vehicle', 'serviceCenter']),
            'message' => 'Warranty claim created successfully'
        ], 201);
    }

    /**
     * Display the specified warranty claim
     */
    public function show($id): JsonResponse
    {
        $warrantyClaim = WarrantyClaim::with(['customer', 'vehicle', 'serviceCenter', 'parts'])
            ->find($id);

        if (!$warrantyClaim) {
            return response()->json([
                'success' => false,
                'message' => 'Warranty claim not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $warrantyClaim,
            'message' => 'Warranty claim retrieved successfully'
        ]);
    }

    /**
     * Update the specified warranty claim
     */
    public function update(Request $request, $id): JsonResponse
    {
        $warrantyClaim = WarrantyClaim::find($id);

        if (!$warrantyClaim) {
            return response()->json([
                'success' => false,
                'message' => 'Warranty claim not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,in_progress,completed,rejected,cancelled',
            'priority' => 'sometimes|required|in:low,medium,high,critical',
            'description' => 'sometimes|required|string|max:1000',
            'estimated_cost' => 'sometimes|numeric|min:0',
            'actual_cost' => 'sometimes|numeric|min:0',
            'assigned_to' => 'sometimes|integer',
            'service_center_id' => 'sometimes|integer|exists:service_centers,id',
            'completion_notes' => 'sometimes|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $warrantyClaim->status;
        
        $warrantyClaim->update($request->only([
            'status', 'priority', 'description', 'estimated_cost', 
            'actual_cost', 'assigned_to', 'service_center_id', 'completion_notes'
        ]));

        // Send notification if status changed
        if ($request->has('status') && $oldStatus !== $request->status) {
            $this->sendNotification($warrantyClaim->customer_id, 'warranty_status_updated', [
                'claim_number' => $warrantyClaim->claim_number,
                'old_status' => $oldStatus,
                'new_status' => $request->status
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $warrantyClaim->load(['customer', 'vehicle', 'serviceCenter']),
            'message' => 'Warranty claim updated successfully'
        ]);
    }

    /**
     * Update warranty claim status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,rejected,cancelled',
            'notes' => 'sometimes|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $warrantyClaim = WarrantyClaim::find($id);

        if (!$warrantyClaim) {
            return response()->json([
                'success' => false,
                'message' => 'Warranty claim not found'
            ], 404);
        }

        $oldStatus = $warrantyClaim->status;
        
        $warrantyClaim->update([
            'status' => $request->status,
            'completion_notes' => $request->notes
        ]);

        // Send notification
        $this->sendNotification($warrantyClaim->customer_id, 'warranty_status_updated', [
            'claim_number' => $warrantyClaim->claim_number,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'data' => $warrantyClaim,
            'message' => 'Warranty claim status updated successfully'
        ]);
    }

    /**
     * Get warranty policies
     */
    public function getPolicies(): JsonResponse
    {
        $policies = [
            [
                'id' => 1,
                'name' => 'Basic Warranty',
                'duration_months' => 24,
                'coverage' => ['battery', 'motor', 'electrical'],
                'description' => 'Standard 2-year warranty covering main components'
            ],
            [
                'id' => 2,
                'name' => 'Extended Warranty',
                'duration_months' => 60,
                'coverage' => ['battery', 'motor', 'electrical', 'software', 'accessories'],
                'description' => 'Extended 5-year comprehensive warranty'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $policies,
            'message' => 'Warranty policies retrieved successfully'
        ]);
    }

    /**
     * Verify customer exists
     */
    private function verifyCustomer($customerId): bool
    {
        try {
            $response = Http::get(config('services.customer.url') . "/api/public/customers/{$customerId}");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check vehicle warranty status
     */
    private function checkVehicleWarranty($vin): array
    {
        try {
            $response = Http::get(config('services.vehicle.url') . "/api/vehicles/{$vin}/warranty");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'valid' => $data['data']['warranty_active'] ?? false,
                    'expires_at' => $data['data']['warranty_end_date'] ?? null
                ];
            }
            
            return ['valid' => false];
        } catch (\Exception $e) {
            return ['valid' => false];
        }
    }

    /**
     * Send notification to customer
     */
    private function sendNotification($customerId, $type, $data): void
    {
        try {
            Http::post(config('services.notification.url') . '/api/notifications', [
                'customer_id' => $customerId,
                'type' => $type,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            logger('Failed to send notification: ' . $e->getMessage());
        }
    }
}