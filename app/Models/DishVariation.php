<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'food_dish_id',
        'name',
        'price',
        // 'variation_description',
        // 'sku',
    ];

    // Cast price to float or use a dedicated Money library
    protected $casts = [
        'price' => 'decimal:2',
    ];

    // Each variation belongs to one dish
    public function foodDish(): BelongsTo
    {
        return $this->belongsTo(FoodDishes::class);
    }
}
