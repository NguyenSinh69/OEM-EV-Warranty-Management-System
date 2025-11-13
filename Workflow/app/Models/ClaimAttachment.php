<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_claim_id',
        'file_name',
        'file_type',
        'file_size',
        'file_path',
        'description',
        'uploaded_by',
    ];

    /**
     * Get the warranty claim that owns the attachment.
     */
    public function warrantyClaim(): BelongsTo
    {
        return $this->belongsTo(WarrantyClaim::class);
    }

    /**
     * Get file size in human readable format.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return in_array($this->file_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ]);
    }
}