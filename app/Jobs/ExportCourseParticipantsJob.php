<?php

namespace App\Jobs;

use App\Exports\CourseParticipantsExport;
use App\Models\ExportHistory;
use App\Notifications\ExportCompletedNotification;
use App\Services\CourseParticipantQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportCourseParticipantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(protected ExportHistory $exportHistory) {}

    public function handle(CourseParticipantQueryService $queryService): void
    {
        $history = $this->exportHistory;
        $course  = $history->course;

        try {
            $query = $queryService->buildQuery(
                $course,
                $history->filter,
                $history->course_class_id
            );

            $filename = sprintf(
                'exports/peserta-%s-%s-%s.xlsx',
                Str::slug($course->title),
                $history->filter,
                now()->format('Ymd_His')
            );

            Excel::store(
                new CourseParticipantsExport($query, $course->program_type ?? 'regular'),
                $filename,
                'local'
            );

            $history->update([
                'file_path' => $filename,
                'status'    => 'done',
            ]);

            $history->user->notify(new ExportCompletedNotification($history->fresh()));

        } catch (\Throwable $e) {
            Log::error('ExportCourseParticipantsJob failed', [
                'export_id' => $history->id,
                'error'     => $e->getMessage(),
            ]);

            $history->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->exportHistory->update([
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
