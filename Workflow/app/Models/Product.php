<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'vin',
        'model',
        'brand',
        'year',
        'battery_capacity',
        'warranty_start_date',
        'warranty_end_date',
        'purchase_date',
        'dealer_id',
        'specifications',
    ];

    protected $casts = [
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'purchase_date' => 'date',
        'specifications' => 'array',
        'battery_capacity' => 'decimal:2',
    ];

    /**
     * Get the full product name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->brand . ' ' . $this->model . ' (' . $this->year . ')';
    }

    /**
     * Check if warranty is still valid.
     */
    public function getIsWarrantyValidAttribute(): bool
    {
        return Carbon::now()->lessThanOrEqualTo($this->warranty_end_date);
    }

    /**
     * Get remaining warranty days.
     */
    public function getRemainingWarrantyDaysAttribute(): int
    {
        if (!$this->is_warranty_valid) {
            return 0;
        }
        
        return Carbon::now()->diffInDays($this->warranty_end_date);
    }

    /**
     * Get all warranty claims for this product.
     */
    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }
}