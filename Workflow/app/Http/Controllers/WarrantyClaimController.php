<?php

namespace App\Http\Controllers;

use App\Models\WarrantyClaim;
use App\Models\Customer;
use App\Models\Product;
use App\Services\WarrantyWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class WarrantyClaimController extends Controller
{
    protected WarrantyWorkflowService $workflowService;

    public function __construct(WarrantyWorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Display a listing of warranty claims.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WarrantyClaim::with(['customer', 'product', 'statusChanges'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('status') && $request->status) {
            $statuses = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('status', $statuses);
        }

        if ($request->has('priority') && $request->priority) {
            $priorities = is_array($request->priority) ? $request->priority : [$request->priority];
            $query->whereIn('priority', $priorities);
        }

        if ($request->has('claim_type') && $request->claim_type) {
            $claimTypes = is_array($request->claim_type) ? $request->claim_type : [$request->claim_type];
            $query->whereIn('claim_type', $claimTypes);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('claim_number', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('overdue') && $request->overdue) {
            $query->whereNotNull('due_date')
                  ->where('due_date', '<', now())
                  ->whereNotIn('status', [WarrantyClaim::STATUS_COMPLETED, WarrantyClaim::STATUS_CANCELLED]);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $claims = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $claims,
            'workflow_stats' => $this->workflowService->getWorkflowStatistics(),
        ]);
    }

    /**
     * Store a newly created warranty claim.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'claim_type' => ['required', Rule::in([
                WarrantyClaim::CLAIM_TYPE_MANUFACTURING_DEFECT,
                WarrantyClaim::CLAIM_TYPE_NORMAL_WEAR,
                WarrantyClaim::CLAIM_TYPE_ACCIDENTAL_DAMAGE,
                WarrantyClaim::CLAIM_TYPE_ELECTRICAL_ISSUE,
                WarrantyClaim::CLAIM_TYPE_BATTERY_ISSUE,
                WarrantyClaim::CLAIM_TYPE_SOFTWARE_ISSUE,
            ])],
            'title' => 'required|string|min:10|max:255',
            'description' => 'required|string|min:20|max:5000',
            'issue_date' => 'required|date|before_or_equal:today',
            'reported_mileage' => 'required|integer|min:0',
            'priority' => ['required', Rule::in([
                WarrantyClaim::PRIORITY_LOW,
                WarrantyClaim::PRIORITY_MEDIUM,
                WarrantyClaim::PRIORITY_HIGH,
                WarrantyClaim::PRIORITY_CRITICAL,
            ])],
            'attachments.*' => 'nullable|file|mimes:jpeg,png,gif,pdf,mp4|max:10240', // 10MB
        ]);

        try {
            $claim = WarrantyClaim::create($validated);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('warranty-attachments', 'public');
                    
                    $claim->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'file_path' => $path,
                        'uploaded_by' => $request->user()->id ?? 'system',
                    ]);
                }
            }

            $claim->load(['customer', 'product', 'attachments', 'statusChanges']);

            return response()->json([
                'success' => true,
                'message' => 'Yêu cầu bảo hành đã được tạo thành công',
                'data' => $claim,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo yêu cầu bảo hành',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified warranty claim.
     */
    public function show(WarrantyClaim $warrantyClaim): JsonResponse
    {
        $warrantyClaim->load(['customer', 'product', 'attachments', 'statusChanges']);

        $availableTransitions = $this->workflowService->getAvailableTransitions(
            $warrantyClaim->status,
            request()->user()->role ?? 'admin' // Default to admin for demo
        );

        return response()->json([
            'success' => true,
            'data' => $warrantyClaim,
            'available_transitions' => $availableTransitions,
        ]);
    }

    /**
     * Update warranty claim status.
     */
    public function updateStatus(Request $request, WarrantyClaim $warrantyClaim): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                WarrantyClaim::STATUS_SUBMITTED,
                WarrantyClaim::STATUS_UNDER_REVIEW,
                WarrantyClaim::STATUS_APPROVED,
                WarrantyClaim::STATUS_REJECTED,
                WarrantyClaim::STATUS_PROCESSING,
                WarrantyClaim::STATUS_COMPLETED,
                WarrantyClaim::STATUS_CANCELLED,
            ])],
            'reason' => 'nullable|string|max:1000',
        ]);

        try {
            $userRole = $request->user()->role ?? 'admin'; // Default to admin for demo
            $userId = $request->user()->id ?? 'system';

            $updatedClaim = $this->workflowService->changeStatus(
                $warrantyClaim,
                $validated['status'],
                $userRole,
                $userId,
                $validated['reason'] ?? null
            );

            $updatedClaim->load(['customer', 'product', 'statusChanges']);

            return response()->json([
                'success' => true,
                'message' => 'Trạng thái đã được cập nhật thành công',
                'data' => $updatedClaim,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get warranty claim statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->workflowService->getWorkflowStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get available status transitions for a claim.
     */
    public function getAvailableTransitions(WarrantyClaim $warrantyClaim): JsonResponse
    {
        $userRole = request()->user()->role ?? 'admin';
        
        $transitions = $this->workflowService->getAvailableTransitions(
            $warrantyClaim->status,
            $userRole
        );

        return response()->json([
            'success' => true,
            'data' => $transitions,
        ]);
    }
}