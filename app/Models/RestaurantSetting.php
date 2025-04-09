<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'is_portal_active',
        'is_restaurant_active',
        'has_orders',
        'has_meals'
    ];


    public function restaurant()
    {
        return $this->belongsTo(Restaurants::class, 'restaurants_id');
    }
}
