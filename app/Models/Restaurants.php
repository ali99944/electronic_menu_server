<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurants extends Model
{
    protected $table = 'restaurants';

    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
        'image',
        'logo',

        'currency',
        'currency_icon',
        'phone',
        'whatsapp',
        'email',
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
