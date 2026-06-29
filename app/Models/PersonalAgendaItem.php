<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agenda pribadi milik peserta. Murni per-user; tidak terkait course manapun.
 */
class PersonalAgendaItem extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'note',
        'event_date',
        'hour',
        'minute',
    ];

    protected $casts = [
        'event_date' => 'date',
        'hour' => 'integer',
        'minute' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
