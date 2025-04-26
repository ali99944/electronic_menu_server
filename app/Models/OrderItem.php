<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'name',
        'price',
        'image',
        'quantity',
        'orders_id',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class);
    }

    public function selected_extras()
    {
        return $this->belongsToMany(
                DishExtra::class,       // Related Model
                'order_item_dish_extra', // Pivot table name
                'order_items_id',         // Foreign key on pivot table for this model
                'dish_extra_id'         // Foreign key on pivot table for the related model
            );
            // If you added extra pivot columns like 'quantity' or 'price_at_addition':
            // ->withPivot('quantity', 'price_at_addition');
    }
}
