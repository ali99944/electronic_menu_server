<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurants extends Model
{
    protected $table = 'restaurants';

    protected $fillable = [
        'name',
        'description',
        'image',
        'logo'
    ];

    public function settings()
    {
        return $this->hasOne(RestaurantSetting::class, 'restaurants_id');
    }

    public function styles()
    {
        return $this->hasOne(RestaurantMenuStyle::class, 'restaurants_id');
    }
}
