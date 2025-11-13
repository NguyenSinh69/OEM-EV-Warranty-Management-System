<?php

namespace App\Services;

use App\Models\WarrantyClaim;
use App\Models\StatusChange;
use Exception;

class WarrantyWorkflowService
{
    /**
     * Available state transitions with rules
     */
    private array $transitions = [
        WarrantyClaim::STATUS_SUBMITTED => [
            [
                'to' => WarrantyClaim::STATUS_UNDER_REVIEW,
                'action' => 'start_review',
                'allowed_roles' => ['admin', 'reviewer'],
                'requires_reason' => false,
            ],
            [
                'to' => WarrantyClaim::STATUS_REJECTED,
                'action' => 'reject',
                'allowed_roles' => ['admin', 'reviewer'],
                'requires_reason' => true,
            ],
        ],
        WarrantyClaim::STATUS_UNDER_REVIEW => [
            [
                'to' => WarrantyClaim::STATUS_APPROVED,
                'action' => 'approve',
                'allowed_roles' => ['admin', 'reviewer'],
                'requires_reason' => false,
            ],
            [
                'to' => WarrantyClaim::STATUS_REJECTED,
                'action' => 'reject',
                'allowed_roles' => ['admin', 'reviewer'],
                'requires_reason' => true,
            ],
            [
                'to' => WarrantyClaim::STATUS_SUBMITTED,
                'action' => 'request_more_info',
                'allowed_roles' => ['admin', 'reviewer'],
                'requires_reason' => true,
            ],
        ],
        WarrantyClaim::STATUS_APPROVED => [
            [
                'to' => WarrantyClaim::STATUS_PROCESSING,
                'action' => 'start_processing',
                'allowed_roles' => ['admin', 'technician'],
                'requires_reason' => false,
            ],
            [
                'to' => WarrantyClaim::STATUS_CANCELLED,
                'action' => 'cancel',
                'allowed_roles' => ['admin'],
                'requires_reason' => true,
            ],
        ],
        WarrantyClaim::STATUS_PROCESSING => [
            [
                'to' => WarrantyClaim::STATUS_COMPLETED,
                'action' => 'complete',
                'allowed_roles' => ['admin', 'technician'],
                'requires_reason' => false,
            ],
            [
                'to' => WarrantyClaim::STATUS_CANCELLED,
                'action' => 'cancel',
                'allowed_roles' => ['admin'],
                'requires_reason' => true,
            ],
        ],
        WarrantyClaim::STATUS_REJECTED => [
            [
                'to' => WarrantyClaim::STATUS_SUBMITTED,
                'action' => 'resubmit',
                'allowed_roles' => ['customer', 'admin'],
                'requires_reason' => false,
            ],
        ],
    ];

    /**
     * Get available transitions for a given status and role.
     */
    public function getAvailableTransitions(string $currentStatus, string $userRole): array
    {
        if (!isset($this->transitions[$currentStatus])) {
            return [];
        }

        return array_filter($this->transitions[$currentStatus], function ($transition) use ($userRole) {
            return in_array($userRole, $transition['allowed_roles']);
        });
    }

    /**
     * Validate if a transition is allowed.
     */
    public function validateTransition(
        string $fromStatus, 
        string $toStatus, 
        string $userRole, 
        ?string $reason = null
    ): array {
        $availableTransitions = $this->getAvailableTransitions($fromStatus, $userRole);
        
        $transition = collect($availableTransitions)->firstWhere('to', $toStatus);
        
        if (!$transition) {
            return [
                'valid' => false,
                'error' => "Không thể chuyển từ trạng thái '{$fromStatus}' sang '{$toStatus}' với vai trò '{$userRole}'"
            ];
        }

        if ($transition['requires_reason'] && empty($reason)) {
            return [
                'valid' => false,
                'error' => 'Yêu cầu nhập lý do cho việc thay đổi trạng thái này'
            ];
        }

        return [
            'valid' => true,
            'transition' => $transition
        ];
    }

    /**
     * Execute status change with validation.
     */
    public function changeStatus(
        WarrantyClaim $claim, 
        string $newStatus, 
        string $userRole,
        string $userId,
        ?string $reason = null
    ): WarrantyClaim {
        $validation = $this->validateTransition(
            $claim->status, 
            $newStatus, 
            $userRole, 
            $reason
        );

        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        $oldStatus = $claim->status;

        // Update claim status
        $claim->status = $newStatus;
        
        // Set completed_at if status is COMPLETED
        if ($newStatus === WarrantyClaim::STATUS_COMPLETED) {
            $claim->completed_at = now();
        }

        $claim->save();

        // Record status change
        StatusChange::create([
            'warranty_claim_id' => $claim->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => $userId,
            'notes' => $this->generateStatusChangeNote($oldStatus, $newStatus, $reason),
        ]);

        return $claim->fresh();
    }

    /**
     * Get action display name.
     */
    public function getActionDisplayName(string $action): string
    {
        $names = [
            'start_review' => 'Bắt đầu xem xét',
            'approve' => 'Phê duyệt',
            'reject' => 'Từ chối',
            'request_more_info' => 'Yêu cầu thêm thông tin',
            'start_processing' => 'Bắt đầu xử lý',
            'complete' => 'Hoàn thành',
            'cancel' => 'Hủy bỏ',
            'resubmit' => 'Gửi lại'
        ];

        return $names[$action] ?? $action;
    }

    /**
     * Get status color class for UI.
     */
    public function getStatusColor(string $status): string
    {
        $colors = [
            WarrantyClaim::STATUS_SUBMITTED => 'bg-blue-100 text-blue-800',
            WarrantyClaim::STATUS_UNDER_REVIEW => 'bg-yellow-100 text-yellow-800',
            WarrantyClaim::STATUS_APPROVED => 'bg-green-100 text-green-800',
            WarrantyClaim::STATUS_REJECTED => 'bg-red-100 text-red-800',
            WarrantyClaim::STATUS_PROCESSING => 'bg-purple-100 text-purple-800',
            WarrantyClaim::STATUS_COMPLETED => 'bg-gray-100 text-gray-800',
            WarrantyClaim::STATUS_CANCELLED => 'bg-gray-100 text-gray-800',
        ];

        return $colors[$status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Check if claim meets business rules for transition.
     */
    public function validateBusinessRules(WarrantyClaim $claim, string $newStatus): array
    {
        $errors = [];

        // Check warranty validity
        if (!$claim->product->is_warranty_valid && $newStatus === WarrantyClaim::STATUS_APPROVED) {
            $errors[] = 'Sản phẩm đã hết hạn bảo hành';
        }

        // Check if claim type is covered
        if ($claim->claim_type === WarrantyClaim::CLAIM_TYPE_ACCIDENTAL_DAMAGE && 
            $newStatus === WarrantyClaim::STATUS_APPROVED) {
            $errors[] = 'Hư hỏng do tai nạn không được bảo hành';
        }

        // Check mileage limits (example: over 100k km may not be covered)
        if ($claim->reported_mileage > 100000 && 
            $newStatus === WarrantyClaim::STATUS_APPROVED) {
            $errors[] = 'Số km đã vượt quá giới hạn bảo hành';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Generate automatic status change note.
     */
    private function generateStatusChangeNote(string $fromStatus, string $toStatus, ?string $reason): string
    {
        $note = "Trạng thái được thay đổi từ {$fromStatus} sang {$toStatus}";
        
        if ($reason) {
            $note .= ". Lý do: {$reason}";
        }

        return $note;
    }

    /**
     * Get workflow statistics.
     */
    public function getWorkflowStatistics(): array
    {
        $total = WarrantyClaim::count();
        
        $statusCounts = WarrantyClaim::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $avgProcessingTime = WarrantyClaim::whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, created_at, completed_at)) as avg_days')
            ->value('avg_days');

        $overdueCount = WarrantyClaim::whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereNotIn('status', [WarrantyClaim::STATUS_COMPLETED, WarrantyClaim::STATUS_CANCELLED])
            ->count();

        return [
            'total_claims' => $total,
            'status_distribution' => $statusCounts,
            'avg_processing_days' => round($avgProcessingTime ?? 0, 1),
            'overdue_claims' => $overdueCount,
        ];
    }
}