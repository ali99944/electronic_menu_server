<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangelogPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'changelog_version_id',
        'type',
        'description',
    ];

    /**
     * The allowed types for a changelog point.
     */
    public const ALLOWED_TYPES = ['new', 'improvement', 'fix'];


    /**
     * Get the version that the point belongs to.
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(ChangelogVersion::class, 'changelog_version_id');
    }
}
