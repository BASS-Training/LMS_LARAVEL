<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackQuestion extends Model
{
    protected $fillable = [
        'content_id',
        'type',
        'question',
        'help_text',
        'is_required',
        'order',
        'config',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order' => 'integer',
        'config' => 'array',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function answers()
    {
        return $this->hasMany(FeedbackAnswer::class, 'question_id');
    }

    /**
     * Daftar opsi (untuk single_choice / multi_choice).
     */
    public function options(): array
    {
        return $this->config['options'] ?? [];
    }
}
