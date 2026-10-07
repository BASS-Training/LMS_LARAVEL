<?php

namespace App\Observers;

use App\Models\EssaySubmission;
use App\Notifications\FinalAssessmentResultNotification;
use App\Notifications\MobileNotification;
use App\Observers\Concerns\NotifiesInstructors;

/**
 * Notifies the participant when their essay submission gets graded/reviewed, and
 * the course instructors when a new essay is submitted. Hooks `created` (new
 * submission) and `updated` (status changes: submitted → notify instructor,
 * graded/reviewed → notify participant), so it fires for both web and mobile.
 */
class EssaySubmissionObserver
{
    use NotifiesInstructors;

    public function created(EssaySubmission $submission): void
    {
        if ($submission->status === 'submitted') {
            $this->notifyInstructorsOfSubmission($submission, 'essay', 'essay');
        }
    }

    public function updated(EssaySubmission $submission): void
    {
        if (! $submission->wasChanged('status')) {
            return;
        }
        if ($submission->status === 'submitted') {
            $this->notifyInstructorsOfSubmission($submission, 'essay', 'essay');

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
        $user->notify(new FinalAssessmentResultNotification(
            assessmentType: 'essay',
            submissionId: $submission->id,
            contentId: $content->id,
            contentTitle: $content->title,
            courseTitle: $course?->title ?? 'Course BASS Academy',
            status: $submission->status,
        ));
    }
}
