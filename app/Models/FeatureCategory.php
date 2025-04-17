<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'icon_name',
        'display_order',
    ];

    /**
     * Get the features associated with the category.
     */
    public function features(): HasMany
    {
        // Order features by their display_order within the category
        return $this->hasMany(Feature::class)->orderBy('display_order');
    }
}
