<?php

namespace App\Observers;

use App\Models\DocumentSubmission;
use App\Notifications\MobileNotification;

/**
 * Memberi tahu peserta ketika pengumpulan dokumennya selesai dinilai.
 * Hook `updated` agar berlaku untuk penilaian dari web maupun mobile.
 */
class DocumentSubmissionObserver
{
    public function updated(DocumentSubmission $submission): void
    {
        if (! $submission->wasChanged('status')) {
            return;
        }
        // Hanya saat berpindah ke status terisi nilai (lulus / belum lulus).
        if (! in_array($submission->status, ['passed', 'failed'], true)) {
            return;
        }

        $submission->loadMissing(['content.lesson.course', 'user']);
        $user = $submission->user;
        if (! $user) {
            return;
        }

        $content = $submission->content;
        $course = $content?->lesson?->course;
        $passed = $submission->status === 'passed';
        $title = $content?->title ?? 'Tugas';

        $user->notify(new MobileNotification([
            'category' => 'grade',
            'title' => $passed ? 'Tugas dinilai LULUS' : 'Tugas perlu revisi',
            'message' => 'Pengumpulan "'.$title.'"'
                .($course ? ' di '.$course->title : '')
                .($passed
                    ? ' telah dinilai LULUS.'
                    : ' dinilai belum lulus — silakan unggah percobaan berikutnya.'),
            'courseId' => $course ? (string) $course->id : null,
            'courseTitle' => $course?->title,
            'contentId' => $content ? (string) $content->id : null,
        ]));
    }
}
