<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Pengumpulan tugas berupa dokumen dari peserta (konten tipe 'document'
 * dengan collect_submission = true). Satu baris per attempt.
 *
 * Alur status: draft -> submitted -> passed | failed.
 * Bila 'failed' peserta boleh membuat attempt berikutnya (lihat controller).
 */
class DocumentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_id',
        'attempt',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
        'status',
        'score',
        'feedback',
        'submitted_at',
        'graded_at',
        'graded_by',
    ];

    protected $casts = [
        'attempt' => 'integer',
        'file_size' => 'integer',
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

    /** Masih boleh diubah peserta (upload/ganti/hapus file). */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /** Sudah dikumpulkan & terkunci (menunggu nilai atau sudah dinilai). */
    public function isLocked(): bool
    {
        return in_array($this->status, ['submitted', 'passed', 'failed'], true);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isGraded(): bool
    {
        return in_array($this->status, ['passed', 'failed'], true);
    }

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /** URL publik file (null bila belum ada file). */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    /** Label status siap-tampil (Bahasa Indonesia). */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Belum dikumpulkan',
            'submitted' => 'Menunggu penilaian',
            'passed' => 'Lulus',
            'failed' => 'Belum lulus',
            default => ucfirst((string) $this->status),
        };
    }
}
