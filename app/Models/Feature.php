<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_category_id',
        'name',
        'description',
        'icon_name',
        'available_in_base',
        'display_order',
    ];

    /**
     * Casts for boolean field.
     */
    protected $casts = [
        'available_in_base' => 'boolean',
    ];

    /**
     * Get the category that the feature belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FeatureCategory::class, 'feature_category_id');
    }
}
