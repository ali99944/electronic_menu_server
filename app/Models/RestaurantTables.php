<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTables extends Model
{
    protected $fillable = [
        'table_number',
        'status'
    ];

    public function orders()
    {
        return $this->hasMany(Orders::class);
    }
}
