<?php

namespace App\Observers;

use App\Models\DocumentSubmission;
use App\Notifications\FinalAssessmentResultNotification;
use App\Notifications\MobileNotification;
use App\Observers\Concerns\NotifiesInstructors;

/**
 * Memberi tahu peserta ketika pengumpulan dokumennya selesai dinilai, dan
 * instruktur ketika ada pengumpulan dokumen baru. Hook `created` + `updated`
 * agar berlaku untuk web maupun mobile.
 */
class DocumentSubmissionObserver
{
    use NotifiesInstructors;

    public function created(DocumentSubmission $submission): void
    {
        if ($submission->status === 'submitted') {
            $this->notifyInstructorsOfSubmission($submission, 'document', 'dokumen');
        }
    }

    public function updated(DocumentSubmission $submission): void
    {
        if (! $submission->wasChanged('status')) {
            return;
        }
        if ($submission->status === 'submitted') {
            $this->notifyInstructorsOfSubmission($submission, 'document', 'dokumen');

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
        $user->notify(new FinalAssessmentResultNotification(
            assessmentType: 'document',
            submissionId: $submission->id,
            contentId: $content->id,
            contentTitle: $content->title,
            courseTitle: $course?->title ?? 'Course BASS Academy',
            status: $submission->status,
            attempt: $submission->attempt,
        ));
    }
}
