<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'notes',
    ];

    /**
     * Get the warranty claim that owns the status change.
     */
    public function warrantyClaim(): BelongsTo
    {
        return $this->belongsTo(WarrantyClaim::class);
    }

    /**
     * Get from status display name.
     */
    public function getFromStatusDisplayNameAttribute(): string
    {
        return $this->getStatusDisplayName($this->from_status);
    }

    /**
     * Get to status display name.
     */
    public function getToStatusDisplayNameAttribute(): string
    {
        return $this->getStatusDisplayName($this->to_status);
    }

    /**
     * Get status display name.
     */
    private function getStatusDisplayName(string $status): string
    {
        $names = [
            'SUBMITTED' => 'Đã gửi',
            'UNDER_REVIEW' => 'Đang xem xét',
            'APPROVED' => 'Đã phê duyệt',
            'REJECTED' => 'Bị từ chối',
            'PROCESSING' => 'Đang xử lý',
            'COMPLETED' => 'Hoàn thành',
            'CANCELLED' => 'Đã hủy',
        ];

        return $names[$status] ?? $status;
    }
}