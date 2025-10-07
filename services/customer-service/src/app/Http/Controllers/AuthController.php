<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    /**
     * Customer login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simulate customer lookup (replace with actual Customer model)
        $customer = $this->findCustomerByEmail($request->email);

        if (!$customer || !Hash::check($request->password, $customer['password'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Generate JWT token
        $payload = [
            'iss' => config('app.url'),
            'sub' => $customer['id'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'user_type' => 'customer',
            'email' => $customer['email']
        ];

        $jwt = JWT::encode($payload, config('app.jwt_secret'), 'HS256');

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'token' => $jwt,
                'token_type' => 'Bearer',
                'expires_in' => 24 * 60 * 60
            ],
            'message' => 'Login successful'
        ]);
    }

    /**
     * Customer registration
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'date_of_birth' => 'required|date',
            'id_number' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simulate customer creation (replace with actual Customer model)
        $customer = [
            'id' => uniqid(),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'id_number' => $request->id_number,
            'password' => Hash::make($request->password),
            'status' => 'active',
            'created_at' => now()
        ];

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Registration successful'
        ], 201);
    }

    /**
     * Get authenticated customer profile
     */
    public function profile(Request $request): JsonResponse
    {
        $customerId = $request->user_id; // From JWT middleware

        $customer = $this->findCustomerById($customerId);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Profile retrieved successfully'
        ]);
    }

    /**
     * Customer logout
     */
    public function logout(Request $request): JsonResponse
    {
        // In a real implementation, you might want to blacklist the token
        return response()->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Refresh JWT token
     */
    public function refresh(Request $request): JsonResponse
    {
        $customerId = $request->user_id; // From JWT middleware

        $customer = $this->findCustomerById($customerId);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        // Generate new JWT token
        $payload = [
            'iss' => config('app.url'),
            'sub' => $customer['id'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'user_type' => 'customer',
            'email' => $customer['email']
        ];

        $jwt = JWT::encode($payload, config('app.jwt_secret'), 'HS256');

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $jwt,
                'token_type' => 'Bearer',
                'expires_in' => 24 * 60 * 60
            ],
            'message' => 'Token refreshed successfully'
        ]);
    }

    /**
     * Find customer by email (mock function)
     */
    private function findCustomerByEmail($email): ?array
    {
        // Mock customer data - replace with actual database query
        $customers = [
            [
                'id' => 1,
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'phone' => '0901234567',
                'address' => 'Hà Nội',
                'date_of_birth' => '1990-01-01',
                'id_number' => '123456789',
                'password' => Hash::make('password123'),
                'status' => 'active'
            ]
        ];

        foreach ($customers as $customer) {
            if ($customer['email'] === $email) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Find customer by ID (mock function)
     */
    private function findCustomerById($id): ?array
    {
        // Mock customer data - replace with actual database query
        $customers = [
            [
                'id' => 1,
                'name' => 'Nguyễn Văn A',
                'email' => 'nguyenvana@example.com',
                'phone' => '0901234567',
                'address' => 'Hà Nội',
                'date_of_birth' => '1990-01-01',
                'id_number' => '123456789',
                'status' => 'active'
            ]
        ];

        foreach ($customers as $customer) {
            if ($customer['id'] == $id) {
                return $customer;
            }
        }

        return null;
    }
}