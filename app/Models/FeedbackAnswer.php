<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackAnswer extends Model
{
    protected $fillable = [
        'submission_id',
        'question_id',
        'rating_value',
        'text_value',
        'choice_value',
    ];

    protected $casts = [
        'rating_value' => 'integer',
        'choice_value' => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(FeedbackSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(FeedbackQuestion::class, 'question_id');
    }
}
