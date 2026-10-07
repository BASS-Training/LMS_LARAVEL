<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Models\LearningReminderDispatch;
use App\Notifications\LearningReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLearningReminders extends Command
{
    protected $signature = 'learning-reminders:send';

    protected $description = 'Queue H-1 and H-0 email reminders for scheduled learning sessions';

    public function handle(): int
    {
        $now = now();
        $queued = 0;
        $duplicates = 0;

        Content::query()
            ->with('lesson.course')
            ->where('type', 'zoom')
            ->where('is_scheduled', true)
            ->whereNotNull('scheduled_start')
            ->where('scheduled_start', '>', $now)
            ->where('scheduled_start', '<=', $now->copy()->addDay())
            ->whereHas('lesson.course', fn ($query) => $query->where('status', 'published'))
            ->chunkById(100, function ($contents) use ($now, &$queued, &$duplicates) {
                foreach ($contents as $content) {
                    $course = $content->lesson?->course;
                    if (! $course) {
                        continue;
                    }

                    $reminderType = $content->scheduled_start->lte($now->copy()->addHour()) ? 'h_0' : 'h_1';
                    $occurrenceStart = $content->scheduled_start->format('Y-m-d H:i:s');

                    $course->participants()
                        ->where('users.learning_reminder_email_enabled', true)
                        ->whereNotNull('users.email')
                        ->where('users.email', '!=', '')
                        ->chunkById(100, function ($users) use ($content, $course, $reminderType, $occurrenceStart, &$queued, &$duplicates) {
                            foreach ($users as $user) {
                                $created = LearningReminderDispatch::query()->insertOrIgnore([[
                                    'content_id' => $content->id,
                                    'user_id' => $user->id,
                                    'reminder_type' => $reminderType,
                                    'occurrence_start' => $occurrenceStart,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]]);

                                if ($created === 0) {
                                    $duplicates++;

                                    continue;
                                }

                                $dispatch = LearningReminderDispatch::query()
                                    ->where('content_id', $content->id)
                                    ->where('user_id', $user->id)
                                    ->where('reminder_type', $reminderType)
                                    ->where('occurrence_start', $occurrenceStart)
                                    ->firstOrFail();

                                try {
                                    $user->notify(new LearningReminderNotification(
                                        reminderType: $reminderType,
                                        contentId: $content->id,
                                        contentTitle: $content->title,
                                        courseTitle: $course->title,
                                        scheduledStart: $content->scheduled_start->copy(),
                                    ));
                                    $dispatch->update(['queued_at' => now()]);
                                    $queued++;
                                } catch (Throwable $exception) {
                                    $dispatch->delete();
                                    Log::warning('Gagal mengantrekan pengingat pembelajaran', [
                                        'content_id' => $content->id,
                                        'user_id' => $user->id,
                                        'message' => $exception->getMessage(),
                                    ]);
                                }
                            }
                        }, 'users.id', 'id');
                }
            });

        $this->info("{$queued} pengingat diantrikan; {$duplicates} duplikat dilewati.");

        return self::SUCCESS;
    }
}
