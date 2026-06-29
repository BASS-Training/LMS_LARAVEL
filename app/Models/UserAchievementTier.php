<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baseline tier achievement yang sudah dirayakan, per peserta per achievement.
 */
class UserAchievementTier extends Model
{
    protected $fillable = [
        'user_id',
        'achievement_id',
        'tier',
    ];

    protected $casts = [
        'tier' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
