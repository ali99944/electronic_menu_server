<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'name',
        'price',
        'image',
        'quantity',
        'orders_id',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class);
    }
}
