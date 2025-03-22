<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItems extends Model
{
    protected $fillable = [
        'quantity',
        'session_code',
        'food_dishes_id'
    ];

    public function dish()
    {
        return $this->belongsTo(FoodDishes::class, 'food_dishes_id');
    }
}
