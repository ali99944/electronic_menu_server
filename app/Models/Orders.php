<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $fillable = [
        'cost_price',
        'notes',
        'status',
        'restaurant_table_number',
        'client_name',
        'client_location',
        'client_location_landmark',
        'client_phone',
        'order_type',
        'restaurants_id',
        'invoice'

    ];

    public function order_items()
    {
        return $this->hasMany(OrderItem::class, 'orders_id');
    }
}
