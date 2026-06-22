<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_id',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function answers()
    {
        return $this->hasMany(FeedbackAnswer::class, 'submission_id');
    }
}
