<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItems extends Model
{
    protected $fillable = [
        'quantity',
        'session_code',
        'food_dishes_id',
        'selected_dish_variant_name',
        'selected_dish_variant_value'
    ];

    public function dish()
    {
        return $this->belongsTo(FoodDishes::class, 'food_dishes_id');
    }

    // --- NEW: Relationship to selected Dish Extras ---
    // Defines a many-to-many relationship via the pivot table
    public function selectedExtras()
    {
        return $this->belongsToMany(
                DishExtra::class,       // Related Model
                'cart_item_dish_extra', // Pivot table name
                'cart_item_id',         // Foreign key on pivot table for this model
                'dish_extra_id'         // Foreign key on pivot table for the related model
            );
            // If you added extra pivot columns like 'quantity' or 'price_at_addition':
            // ->withPivot('quantity', 'price_at_addition');
    }
}
