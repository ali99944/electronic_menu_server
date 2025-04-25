<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodDishes extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'food_varieties_id',
        'restaurants_id'
    ];

    public function variety()
    {
        return $this->belongsTo(FoodVarieties::class, 'food_varieties_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurants::class);
    }

    // A dish has many variations/pricing options
    public function variations()
    {
        return $this->hasMany(DishVariation::class);
    }

    // --- NEW: Relationship to Extras ---
    public function extras()
    {
        // Ensure foreign key matches your dish_extras table
        return $this->hasMany(DishExtra::class, 'food_dishes_id');
    }
}
