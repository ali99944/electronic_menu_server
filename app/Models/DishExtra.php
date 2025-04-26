<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishExtra extends Model
{
    use HasFactory;

    // Adjust table name if needed
    // protected $table = 'dish_extras';

    protected $fillable = [
        'food_dish_id', // Foreign key column name
        'name',
        'price',
    ];

    // Cast price to a specific type (e.g., decimal)
    protected $casts = [
        'price' => 'decimal:2', // Example: Cast to decimal with 2 places
    ];

    // Relationship back to the FoodDish
    public function foodDish(): BelongsTo
    {
        // Ensure foreign key matches your dish_extras table
        return $this->belongsTo(FoodDishes::class, 'food_dishes_id');
    }

    // --- NEW: Relationship to Cart Items that selected this extra ---
    public function cartItems()
    {
        return $this->belongsToMany(
                CartItems::class,
                'cart_item_dish_extra',
                'dish_extra_id',
                'cart_item_id'
            );
             // ->withPivot(...) if needed
    }
}
