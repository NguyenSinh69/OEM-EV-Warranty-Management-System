<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'claim_number',
        'customer_id',
        'vehicle_vin',
        'description',
        'issue_type',
        'priority',
        'status',
        'service_center_id',
        'estimated_cost',
        'actual_cost',
        'assigned_to',
        'completion_notes',
        'created_by'
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the service center for this claim
     */
    public function serviceCenter(): BelongsTo
    {
        return $this->belongsTo(ServiceCenter::class);
    }

    /**
     * Get the parts used in this claim
     */
    public function parts(): HasMany
    {
        return $this->hasMany(WarrantyPart::class);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Check if claim is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if claim is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

class ServiceCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'manager_name',
        'status',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the warranty claims for this service center
     */
    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    /**
     * Scope for active service centers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

class WarrantyPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'part_number',
        'part_name',
        'quantity',
        'unit_cost',
        'total_cost',
        'supplier',
        'status'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'quantity' => 'integer'
    ];

    /**
     * Get the warranty claim for this part
     */
    public function warrantyClaim(): BelongsTo
    {
        return $this->belongsTo(WarrantyClaim::class);
    }
}

class WarrantyPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_months',
        'coverage',
        'terms_conditions',
        'status',
        'vehicle_models',
        'effective_date',
        'expiry_date'
    ];

    protected $casts = [
        'coverage' => 'array',
        'vehicle_models' => 'array',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'duration_months' => 'integer'
    ];

    /**
     * Scope for active policies
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('effective_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', now());
                    });
    }
}