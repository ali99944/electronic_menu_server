<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RestaurantTables extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'table_number',
        'restaurants_id', // Add restaurant_id here
        'qrcode',
        'status',
    ];

    /**
     * Get the restaurant that owns the table.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurants::class, 'restaurant_id'); // Define relationship
    }

     /**
     * Accessor to get the full URL for the QR code image.
     * Assumes you are using the 'public' disk and storage:link is set up.
     */
    public function getQrcodeUrlAttribute(): string|null
    {
        if ($this->qrcode) {
            return Storage::disk('public')->url($this->qrcode);
        }
        return null; // Or return a default placeholder image URL
    }

    // Optional: Append the accessor to JSON/array output
    // protected $appends = ['qrcode_url'];

}
