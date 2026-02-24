<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Question;

class QuizAttempt extends Model
{
    use HasFactory;

    private function resolveQuestionMarks(Question $question): int
    {
        $rawMarks = $question->getAttribute('marks');

        if (is_null($rawMarks)) {
            $rawMarks = $question->getAttribute('mark');
        }

        $marks = is_numeric($rawMarks) ? (int) $rawMarks : 0;

        return max(1, $marks);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quiz_id',
        'user_id',
        'score',
        'passed',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     * INI YANG PENTING! 🔥
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime', // ← TAMBAHKAN INI
        'passed' => 'boolean',
        'score' => 'integer',
    ];

    /**
     * Relasi ke Quiz
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke jawaban-jawaban
     */
    public function answers()
    {
        return $this->hasMany(QuestionAnswer::class, 'quiz_attempt_id');
    }

    /**
     * Accessor untuk mendapatkan waktu selesai yang diformat
     */
    public function getFormattedCompletedAtAttribute()
    {
        if (!$this->completed_at) {
            return 'Belum selesai';
        }

        return $this->completed_at->format('d M Y, H:i');
    }

    /**
     * Accessor untuk mendapatkan durasi pengerjaan
     */
    public function getDurationAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffForHumans($this->started_at, true);
    }

    /**
     * Accessor untuk mendapatkan persentase nilai
     */
    public function getPercentageAttribute()
    {
        $score = $this->score ?? 0;
        if ($score <= 0) {
            return 0;
        }

        $quiz = $this->relationLoaded('quiz') && $this->quiz
            ? $this->quiz
            : $this->quiz()->first();

        if (!$quiz) {
            return 0;
        }

        if (!$quiz->relationLoaded('questions')) {
            $quiz->load('questions');
        }

        $totalMarks = (int) $quiz->questions->sum(fn (Question $question) => $this->resolveQuestionMarks($question));

        if ($totalMarks <= 0) {
            return 0;
        }

        return round(($score / $totalMarks) * 100, 2);
    }
}
