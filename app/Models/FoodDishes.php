<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDishes extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'food_varieties_id',
        'restaurants_id'
    ];

    public function variety()
    {
        return $this->belongsTo(FoodVarieties::class, 'food_varieties_id');
    }

}
