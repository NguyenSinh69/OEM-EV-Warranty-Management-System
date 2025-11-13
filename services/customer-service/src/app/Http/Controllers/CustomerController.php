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
        $customers = Customer::when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
            })
            ->paginate(15);

        // Nếu không có ?email=, trả về tất cả
        $allCustomers = Customer::all();
        return response()->json([
            'success' => true,
            'data' => $allCustomers
        ]);
    }
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
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer retrieved successfully'
        ]);
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:customers,email,' . $id,
            'phone' => 'sometimes|required|string|max:20|unique:customers,phone,' . $id,
            'address' => 'sometimes|required|string|max:500',
            'date_of_birth' => 'sometimes|required|date',
            'id_number' => 'sometimes|required|string|max:20|unique:customers,id_number,' . $id,
            'status' => 'sometimes|required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer->update($request->only([
            'name', 'email', 'phone', 'address', 'date_of_birth', 'id_number', 'status'
        ]));

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer updated successfully'
        ]);
    }

    /**
     * Remove the specified customer
     */
    public function destroy($id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

}