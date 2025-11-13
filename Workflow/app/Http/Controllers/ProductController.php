<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'LIKE', "%{$search}%")
                  ->orWhere('model', 'LIKE', "%{$search}%")
                  ->orWhere('brand', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('warranty_valid') && $request->warranty_valid) {
            if ($request->warranty_valid === 'true') {
                $query->where('warranty_end_date', '>=', now());
            } else {
                $query->where('warranty_end_date', '<', now());
            }
        }

        $products = $query->orderBy('brand')->orderBy('model')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vin' => 'required|string|unique:products,vin|max:17',
            'model' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'battery_capacity' => 'required|numeric|min:0',
            'warranty_start_date' => 'required|date',
            'warranty_end_date' => 'required|date|after:warranty_start_date',
            'purchase_date' => 'required|date|before_or_equal:today',
            'dealer_id' => 'nullable|string|max:255',
            'specifications' => 'nullable|array',
        ]);

        try {
            $product = Product::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Sản phẩm đã được tạo thành công',
                'data' => $product,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo sản phẩm',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['warrantyClaims' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'vin' => 'required|string|unique:products,vin,' . $product->id . '|max:17',
            'model' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'battery_capacity' => 'required|numeric|min:0',
            'warranty_start_date' => 'required|date',
            'warranty_end_date' => 'required|date|after:warranty_start_date',
            'purchase_date' => 'required|date|before_or_equal:today',
            'dealer_id' => 'nullable|string|max:255',
            'specifications' => 'nullable|array',
        ]);

        try {
            $product->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Thông tin sản phẩm đã được cập nhật',
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin sản phẩm',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}