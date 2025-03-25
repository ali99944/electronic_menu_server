<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantMenuStyle extends Model
{
    protected $fillable = [
        'restaurants_id',
        'font_id',

        'menu_background_color',
        'header_bg_color',
        'header_text_color',

        'banner_title_color',
        'banner_description_color',

        'primary_color',
        'primary_text_color'
    ];

    public function font()
    {
        return $this->belongsTo(Font::class);
    }
}
