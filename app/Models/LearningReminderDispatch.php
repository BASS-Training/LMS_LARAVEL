<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningReminderDispatch extends Model
{
    protected $fillable = [
        'content_id',
        'user_id',
        'reminder_type',
        'occurrence_start',
        'queued_at',
    ];

    protected function casts(): array
    {
        return [
            'occurrence_start' => 'datetime',
            'queued_at' => 'datetime',
        ];
    }
}
