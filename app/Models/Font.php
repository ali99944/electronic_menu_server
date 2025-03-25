<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Font extends Model
{
    protected $fillable = [
        'name',
        'description',
        'link',
        'font_category_id'
    ];
}
