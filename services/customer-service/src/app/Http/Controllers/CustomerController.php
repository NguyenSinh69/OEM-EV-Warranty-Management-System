<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{

    /**
     * Display a listing of customers.
     * Sửa lại để lọc theo email (Yêu cầu: filter email)
     */
    public function index(Request $request): JsonResponse
    {
        // Kiểm tra xem có query ?email=... hay không
        if ($request->has('email')) {
            $email = $request->query('email');
            
            // Logic lọc theo email (yêu cầu của ticket)
            $customers = Customer::where('email', $email)->get();
            
            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        }

        // Nếu không có ?email=, trả về tất cả
        $allCustomers = Customer::all();
        return response()->json([
            'success' => true,
            'data' => $allCustomers
        ]);
    }

    /**
     * Store a newly created customer.
     * (Yêu cầu: POST /customers)
     * (Yêu cầu: 400 khi trùng)
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validate dữ liệu
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 400); // 400 Bad Request
        }

        // 2. KIỂM TRA TRÙNG EMAIL (Yêu cầu "400 khi trùng")
        $existingCustomer = Customer::where('email', $request->email)->first();
        if ($existingCustomer) {
            return response()->json([
                'success' => false,
                'message' => 'Email đã tồn tại'
            ], 400); // 400 Bad Request
        }

        // 3. Nếu không trùng, tạo customer mới
        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo customer thành công',
            'data' => $customer
        ], 201); // 201 = Created
    }

    /**
     * Display the specified customer.
     * (Yêu cầu: GET /customers/{id})
     */
    public function show($id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy customer'
            ], 404); // 404 Not Found
        }

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }
}