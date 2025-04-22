<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'is_portal_active',
        'is_restaurant_active',
        'has_orders',
        'has_meals',
        'has_delivery',
        'restaurants_id',
    ];


    protected $casts = [
        'is_portal_active' => 'boolean',
        'is_restaurant_active' => 'boolean',
        'has_orders' => 'boolean',
        'has_meals' => 'boolean',
        'has_delivery' => 'boolean',
    ];


    public function restaurant()
    {
        return $this->belongsTo(Restaurants::class, 'restaurants_id');
    }
}
