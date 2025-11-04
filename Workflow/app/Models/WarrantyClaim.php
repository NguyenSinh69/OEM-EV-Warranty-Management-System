<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class WarrantyClaim extends Model
{
    use HasFactory;

    const STATUS_SUBMITTED = 'SUBMITTED';
    const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';

    const CLAIM_TYPE_MANUFACTURING_DEFECT = 'MANUFACTURING_DEFECT';
    const CLAIM_TYPE_NORMAL_WEAR = 'NORMAL_WEAR';
    const CLAIM_TYPE_ACCIDENTAL_DAMAGE = 'ACCIDENTAL_DAMAGE';
    const CLAIM_TYPE_ELECTRICAL_ISSUE = 'ELECTRICAL_ISSUE';
    const CLAIM_TYPE_BATTERY_ISSUE = 'BATTERY_ISSUE';
    const CLAIM_TYPE_SOFTWARE_ISSUE = 'SOFTWARE_ISSUE';

    const PRIORITY_LOW = 'LOW';
    const PRIORITY_MEDIUM = 'MEDIUM';
    const PRIORITY_HIGH = 'HIGH';
    const PRIORITY_CRITICAL = 'CRITICAL';

    protected $fillable = [
        'claim_number',
        'customer_id',
        'product_id',
        'claim_type',
        'title',
        'description',
        'issue_date',
        'reported_mileage',
        'status',
        'priority',
        'assigned_to',
        'resolution',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'resolution' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = self::generateClaimNumber();
            }

            if (empty($claim->due_date)) {
                $claim->due_date = self::calculateDueDate($claim->priority);
            }
        });
    }

    /**
     * Generate unique claim number.
     */
    public static function generateClaimNumber(): string
    {
        $year = date('Y');
        $lastClaim = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastClaim ? intval(substr($lastClaim->claim_number, -6)) + 1 : 1;
        
        return 'WC' . $year . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate due date based on priority.
     */
    public static function calculateDueDate(string $priority): Carbon
    {
        $daysToAdd = [
            self::PRIORITY_CRITICAL => 1,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_MEDIUM => 7,
            self::PRIORITY_LOW => 14,
        ];

        return Carbon::now()->addDays($daysToAdd[$priority] ?? 7);
    }

    /**
     * Check if claim is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && Carbon::now()->greaterThan($this->due_date) && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        $names = [
            self::STATUS_SUBMITTED => 'Đã gửi',
            self::STATUS_UNDER_REVIEW => 'Đang xem xét',
            self::STATUS_APPROVED => 'Đã phê duyệt',
            self::STATUS_REJECTED => 'Bị từ chối',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];

        return $names[$this->status] ?? $this->status;
    }

    /**
     * Get claim type display name.
     */
    public function getClaimTypeDisplayNameAttribute(): string
    {
        $names = [
            self::CLAIM_TYPE_MANUFACTURING_DEFECT => 'Lỗi sản xuất',
            self::CLAIM_TYPE_NORMAL_WEAR => 'Hao mòn tự nhiên',
            self::CLAIM_TYPE_ACCIDENTAL_DAMAGE => 'Hư hỏng do tai nạn',
            self::CLAIM_TYPE_ELECTRICAL_ISSUE => 'Sự cố điện',
            self::CLAIM_TYPE_BATTERY_ISSUE => 'Sự cố pin',
            self::CLAIM_TYPE_SOFTWARE_ISSUE => 'Sự cố phần mềm',
        ];

        return $names[$this->claim_type] ?? $this->claim_type;
    }

    /**
     * Get priority display name.
     */
    public function getPriorityDisplayNameAttribute(): string
    {
        $names = [
            self::PRIORITY_LOW => 'Thấp',
            self::PRIORITY_MEDIUM => 'Trung bình',
            self::PRIORITY_HIGH => 'Cao',
            self::PRIORITY_CRITICAL => 'Khẩn cấp',
        ];

        return $names[$this->priority] ?? $this->priority;
    }

    /**
     * Get the customer that owns the claim.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the product that owns the claim.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all attachments for this claim.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ClaimAttachment::class);
    }

    /**
     * Get all status changes for this claim.
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(StatusChange::class)->orderBy('created_at', 'desc');
    }
}