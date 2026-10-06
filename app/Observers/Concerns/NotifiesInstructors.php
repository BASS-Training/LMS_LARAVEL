<?php

namespace App\Observers\Concerns;

use App\Notifications\MobileNotification;

/**
 * Memberi tahu instruktur pengampu kelas ketika ada submission tugas baru dari
 * peserta (essay / studi kasus / dokumen). Dipakai bersama oleh observer
 * submission agar logikanya satu tempat & konsisten.
 */
trait NotifiesInstructors
{
    /**
     * @param  \App\Models\EssaySubmission|\App\Models\CaseStudySubmission|\App\Models\DocumentSubmission  $submission
     */
    protected function notifyInstructorsOfSubmission($submission, string $typeKey, string $typeLabel): void
    {
        $submission->loadMissing(['content.lesson.course.instructors', 'user']);

        $content = $submission->content;
        $course = $content?->lesson?->course;
        if (! $course) {
            return;
        }

        $participantName = $submission->user?->name ?? 'Peserta';

        // Instruktur pengampu, kecuali bila kebetulan dia sendiri yang mengumpulkan.
        $instructors = $course->instructors
            ->reject(fn ($i) => (int) $i->id === (int) $submission->user_id);

        if ($instructors->isEmpty()) {
            return;
        }

        $payload = [
            'category' => 'new_submission',
            'title' => 'Tugas baru untuk dinilai',
            'message' => $participantName.' mengumpulkan '.$typeLabel.' "'
                .($content?->title ?? 'tugas').'"'
                .' di '.$course->title.'.',
            'courseId' => (string) $course->id,
            'courseTitle' => $course->title,
            'contentId' => $content ? (string) $content->id : null,
            'lessonTitle' => $content?->title,
            'submissionId' => (string) $submission->id,
            'submissionType' => $typeKey,
            'participantName' => $participantName,
        ];

        foreach ($instructors as $instructor) {
            $instructor->notify(new MobileNotification($payload));
        }
    }
}
