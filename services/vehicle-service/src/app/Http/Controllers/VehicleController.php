<?php

namespace App\Http\Controllers;

use App\Models\Vehicle; // Import Model (File 3)
use Illuminate\Http\Request;
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
     * API 1: Đăng ký xe theo VIN (Nhiệm vụ 2)
     * (Tên là 'register' để khớp với api.php)
     */
    public function register(Request $request) {
        // Kiểm tra dữ liệu
        $validator = Validator::make($request->all(), [
            'vin' => 'required|string|unique:vehicles|size:17',
            'model' => 'required|string',
            'year' => 'required|integer',
            'customer_id' => 'required|integer', // Cần biết xe này của ai
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        // Tạo xe (DÙNG MODEL THẬT, KHÔNG DÙNG MOCK DATA)
        $vehicle = Vehicle::create($request->all());
        return response()->json($vehicle, 201);
    }

    /**
     * API 2: Gắn số seri phụ tùng (Nhiệm vụ 2)
     */
    public function addParts(Request $request, $vin) {
        // (Chưa làm, chỉ tạo sườn)
        return response()->json(['message' => "API (addParts) cho xe $vin đang được xây dựng (Ticket #21)."]);
    }

    /**
     * API 3: Lấy lịch sử dịch vụ (Nhiệm vụ 2)
     * (Tên là 'getHistory' để khớp với api.php)
     */
    public function getHistory(Request $request, $vin) {
         // (Chưa làm, chỉ tạo sườn)
         return response()->json(['message' => "API (getHistory) cho xe $vin đang được xây dựng (Ticket #21)."]);
    }

    /**
     * API 4: Thêm lịch sử dịch vụ (Nhiệm vụ 2)
     */
    public function addService(Request $request, $vin) {
         // (Chưa làm, chỉ tạo sườn)
         return response()->json(['message' => "API (addService) cho xe $vin đang được xây dựng (Ticket #21)."]);
    }
}