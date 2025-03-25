<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FontCategory extends Model
{
    protected $table = 'font_categories';

    protected $fillable = [
        'name',
        'description',
        'total_fonts'
    ];
}
