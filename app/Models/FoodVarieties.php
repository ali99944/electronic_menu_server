<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodVarieties extends Model
{
    protected $fillable = [
        'name',
        'image',
    ];

    public function dishes()
    {
        return $this->hasMany(FoodDishes::class);
    }
}
