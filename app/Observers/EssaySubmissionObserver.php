<?php

namespace App\Observers;

use App\Models\EssaySubmission;
use App\Notifications\MobileNotification;

/**
 * Notifies the participant when their essay submission gets graded/reviewed.
 * Hooks the model's `updated` event so it fires for both web and mobile grading
 * (all grading paths flip `status` to graded/reviewed).
 */
class EssaySubmissionObserver
{
    public function updated(EssaySubmission $submission): void
    {
        if (! $submission->wasChanged('status')) {
            return;
        }
        if (! in_array($submission->status, ['graded', 'reviewed'], true)) {
            return;
        }

        $submission->loadMissing(['content.lesson.course', 'user']);
        $user = $submission->user;
        if (! $user) {
            return;
        }

        $content = $submission->content;
        $course = $content?->lesson?->course;
        $scored = $submission->status === 'graded';

        $user->notify(new MobileNotification([
            'category' => 'grade',
            'title' => $scored ? 'Nilai essay sudah keluar' : 'Essay sudah ditinjau',
            'message' => 'Essay "'.($content?->title ?? 'Tugas').'"'
                .($course ? ' di '.$course->title : '')
                .($scored ? ' telah dinilai.' : ' telah ditinjau instruktur.'),
            'courseId' => $course ? (string) $course->id : null,
            'courseTitle' => $course?->title,
            'contentId' => $content ? (string) $content->id : null,
        ]));
    }
}
