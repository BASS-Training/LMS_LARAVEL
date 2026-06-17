<?php

namespace App\Observers;

use App\Models\CaseStudySubmission;
use App\Notifications\MobileNotification;

/**
 * Notifies the participant when their case-study submission gets graded.
 * Hooks `updated` so it fires for both web and mobile grading paths.
 */
class CaseStudySubmissionObserver
{
    public function updated(CaseStudySubmission $submission): void
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

        $user->notify(new MobileNotification([
            'category' => 'grade',
            'title' => 'Nilai studi kasus sudah keluar',
            'message' => 'Studi kasus "'.($content?->title ?? 'Tugas').'"'
                .($course ? ' di '.$course->title : '')
                .' telah dinilai.',
            'courseId' => $course ? (string) $course->id : null,
            'courseTitle' => $course?->title,
            'contentId' => $content ? (string) $content->id : null,
        ]));
    }
}
