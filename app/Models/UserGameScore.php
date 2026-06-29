<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Best score mini-game per peserta per game.
 */
class UserGameScore extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'best_score',
        'plays',
        'last_played_at',
    ];

    protected $casts = [
        'best_score' => 'integer',
        'plays' => 'integer',
        'last_played_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
