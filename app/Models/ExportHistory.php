<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ExportHistory extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'filter',
        'course_class_id',
        'file_path',
        'status',
        'error_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('local')->exists($this->file_path);
    }
}
