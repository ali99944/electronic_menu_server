<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDishes extends Model
{
    protected $fillable = [
        'name',
        'price',
        'image',
        'food_varieties_id'
    ];

    public function variety()
    {
        return $this->belongsTo(FoodVarieties::class, 'food_varieties_id');
    }

}
