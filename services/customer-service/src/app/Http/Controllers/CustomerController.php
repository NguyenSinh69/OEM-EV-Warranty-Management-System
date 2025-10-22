<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Customer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::with('vehicles')
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%");
            })
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $customers,
            'message' => 'Customers retrieved successfully'
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'phone' => 'required|string|max:20|unique:customers',
            'address' => 'required|string|max:500',
            'date_of_birth' => 'required|date',
            'id_number' => 'required|string|max:20|unique:customers',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'id_number' => $request->id_number,
            'password' => Hash::make($request->password),
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully'
        ], 201);
    }

    /**
     * Display the specified customer
     */
    public function show($id): JsonResponse
    {
        $customer = Customer::with(['vehicles', 'warranties'])->find($id);

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

    /**
     * Get customer's vehicles
     */
    public function getVehicles($id): JsonResponse
    {
        $customer = Customer::with('vehicles')->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer->vehicles,
            'message' => 'Customer vehicles retrieved successfully'
        ]);
    }

    /**
     * Get customer's warranty claims
     */
    public function getWarranties($id): JsonResponse
    {
        $customer = Customer::with('warranties')->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer->warranties,
            'message' => 'Customer warranties retrieved successfully'
        ]);
    }
}