<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $fillable = [
        'cost_price',
        'notes',
        'status',
        'restaurant_tables_id'
    ];

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function restaurant_table()
    {
        return $this->belongsTo(RestaurantTables::class, 'restaurant_tables_id');
    }
}
