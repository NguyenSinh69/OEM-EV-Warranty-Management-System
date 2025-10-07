<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'id_number',
        'password',
        'status',
        'avatar',
        'email_verified_at',
        'phone_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'date',
    ];

    /**
     * Get the vehicles that belong to the customer.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the warranty claims that belong to the customer.
     */
    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    /**
     * Get the notifications that belong to the customer.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if customer is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get customer's full address
     */
    public function getFullAddressAttribute(): string
    {
        return $this->address;
    }

    /**
     * Scope for active customers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for searching customers
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('id_number', 'like', "%{$search}%");
        });
    }
}

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vin',
        'model',
        'year',
        'color',
        'purchase_date',
        'warranty_start_date',
        'warranty_end_date',
        'status'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
    ];

    /**
     * Get the customer that owns the vehicle.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

class Warranty extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vehicle_id',
        'claim_number',
        'description',
        'status',
        'priority',
        'created_by',
        'assigned_to',
        'estimated_cost',
        'actual_cost'
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    /**
     * Get the customer that owns the warranty claim.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'title',
        'message',
        'type',
        'status',
        'sent_at',
        'read_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the notification.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}