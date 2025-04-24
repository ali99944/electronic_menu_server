<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'full_name',
        'business_name',
        'phone_number',
        'email',
        'challenges',
        'current_used_system',
        'prefered_time_to_contact'
    ];
}
