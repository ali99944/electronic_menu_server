<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangelogVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'release_date',
    ];

    /**
     * Get the points associated with the version.
     */
    public function points(): HasMany
    {
        // Ensure correct foreign key if not following convention, but default should work
        return $this->hasMany(ChangelogPoint::class);
    }
}
