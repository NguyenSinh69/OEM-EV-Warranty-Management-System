<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    /**
     * Health check
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'admin-service',
            'timestamp' => now(),
            'version' => '1.0.0'
        ]);
    }

    /**
     * Get system statistics
     */
    public function getStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => 150,
                'total_vehicles' => 120,
                'total_claims' => 45,
                'pending_claims' => 12,
                'completed_claims' => 28,
                'rejected_claims' => 5
            ],
            'message' => 'Statistics retrieved successfully'
        ]);
    }

    /**
     * Get warranty policies
     */
    public function getPolicies(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Basic Warranty',
                    'duration_months' => 24,
                    'coverage' => ['battery', 'motor', 'electrical'],
                    'description' => 'Standard 2-year warranty'
                ],
                [
                    'id' => 2,
                    'name' => 'Extended Warranty', 
                    'duration_months' => 60,
                    'coverage' => ['battery', 'motor', 'electrical', 'software'],
                    'description' => 'Extended 5-year warranty'
                ]
            ],
            'message' => 'Policies retrieved successfully'
        ]);
    }
}