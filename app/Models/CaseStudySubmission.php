<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStudySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_id',
        'answers',
        'status',
        'score',
        'feedback',
        'pdf_path',
        'submitted_at',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function isSubmitted(): bool
    {
        return in_array($this->status, ['submitted', 'graded'], true);
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
    }
}
