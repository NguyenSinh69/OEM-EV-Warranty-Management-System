<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * GET /api/customer/vehicles?ownerId=...  (hoặc ?customerId=...)
     * Trả danh sách "xe của tôi" để Dashboard dùng.
     */
    public function byOwner(Request $request): JsonResponse
    {
        // chấp nhận ownerId hoặc customerId từ FE
        $ownerId = $request->query('ownerId') ?? $request->query('customerId');

        if (!$ownerId) {
            return response()->json(['message' => 'ownerId (hoặc customerId) is required'], 400);
        }

        try {
            // Trong DB bạn đang dùng cột customer_id (do API register yêu cầu trường này)
            $vehicles = Vehicle::query()
                ->where('customer_id', $ownerId)
                ->orderByDesc('created_at')
                ->get(['id', 'vin', 'model', 'year', 'customer_id', 'created_at']);

            return response()->json([
                'items' => $vehicles,
                'count' => $vehicles->count(),
            ], 200);
        } catch (\Throwable $e) {
            // \Log::error('byOwner error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Internal error'], 500);
        }
    }

    /**
     * API 1: Đăng ký xe theo VIN (Nhiệm vụ 2)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vin'         => 'required|string|unique:vehicles|size:17',
            'model'       => 'required|string',
            'year'        => 'required|integer',
            'customer_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $vehicle = Vehicle::create($request->only(['vin','model','year','customer_id']));
        return response()->json($vehicle, 201);
    }

    public function addParts(Request $request, $vin)
    {
        return response()->json(['message' => "API (addParts) cho xe $vin đang được xây dựng (Ticket #21)."]);
    }

    public function getHistory(Request $request, $vin)
    {
        return response()->json(['message' => "API (getHistory) cho xe $vin đang được xây dựng (Ticket #21)."]);
    }

    public function addService(Request $request, $vin)
    {
        return response()->json(['message' => "API (addService) cho xe $vin đang được xây dựng (Ticket #21)."]);
    }
}
