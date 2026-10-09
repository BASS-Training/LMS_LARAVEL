<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePreview extends Model
{
    use HasFactory;

    public const PREVIEWABLE_TYPES = ['text', 'video', 'image'];

    protected $fillable = [
        'course_id',
        'content_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
