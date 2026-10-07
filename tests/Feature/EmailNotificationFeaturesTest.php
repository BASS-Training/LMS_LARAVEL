<?php

namespace Tests\Feature;

use App\Models\CaseStudySubmission;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Content;
use App\Models\Course;
use App\Models\DocumentSubmission;
use App\Models\EssaySubmission;
use App\Models\ExportHistory;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\AvpnVerificationStatusNotification;
use App\Notifications\CertificateIssuedNotification;
use App\Notifications\ExportCompletedNotification;
use App\Notifications\FinalAssessmentResultNotification;
use App\Notifications\LearningReminderNotification;
use App\Services\AvpnVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailNotificationFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_email_is_sent_once_after_pdf_becomes_available(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'Course Sertifikasi']);
        $template = CertificateTemplate::create(['name' => 'Template Email', 'layout_data' => []]);
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_template_id' => $template->id,
            'certificate_code' => 'CERT-EMAIL-001',
            'issued_at' => now(),
        ]);

        Notification::assertNotSentTo($user, CertificateIssuedNotification::class);

        $certificate->update(['path' => 'certificates/CERT-EMAIL-001.pdf']);
        $certificate->update(['path' => 'certificates/CERT-EMAIL-001-v2.pdf']);

        Notification::assertSentToTimes($user, CertificateIssuedNotification::class, 1);
        Notification::assertSentTo($user, CertificateIssuedNotification::class, fn ($notification) => $notification->afterCommit === true
            && $notification->certificateCode === 'CERT-EMAIL-001'
            && str_contains($notification->toMail($user)->render(), 'Course Sertifikasi')
        );
        $this->assertNotNull($certificate->fresh()->issued_email_queued_at);
    }

    public function test_avpn_notifications_only_follow_successful_pending_transitions(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $approved = User::factory()->create(['avpn_verification_status' => 'pending']);
        $rejected = User::factory()->create(['avpn_verification_status' => 'pending']);
        $alreadyApproved = User::factory()->create(['avpn_verification_status' => 'approved']);
        $service = app(AvpnVerificationService::class);

        $changed = $service->transitionPending([$approved->id, $alreadyApproved->id], 'approved', $admin);
        $service->transitionPending([$approved->id], 'approved', $admin);
        $service->transitionPending([$rejected->id], 'rejected', $admin, 'Data belum sesuai.');

        $this->assertCount(1, $changed);
        Notification::assertSentToTimes($approved, AvpnVerificationStatusNotification::class, 1);
        Notification::assertNotSentTo($alreadyApproved, AvpnVerificationStatusNotification::class);
        Notification::assertSentTo($rejected, AvpnVerificationStatusNotification::class, fn ($notification) => $notification->status === 'rejected'
            && $notification->reason === 'Data belum sesuai.'
            && $notification->afterCommit === true
            && str_contains($notification->toMail($rejected)->render(), 'Data belum sesuai.')
        );
    }

    public function test_email_notification_is_queued_after_transition_instead_of_using_smtp_inline(): void
    {
        Queue::fake();
        Mail::fake();
        $admin = User::factory()->create();
        $participant = User::factory()->create(['avpn_verification_status' => 'pending']);

        app(AvpnVerificationService::class)->transitionPending([$participant->id], 'approved', $admin);

        Mail::assertNothingSent();
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->afterCommit === true
            && $job->notification instanceof AvpnVerificationStatusNotification
            && $job->notification->status === 'approved'
        );
    }

    public function test_assessment_observers_email_final_results_without_duplicate_updates(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'Course Penilaian']);
        $lesson = Lesson::factory()->for($course)->create();

        $essayContent = Content::factory()->for($lesson)->create(['title' => 'Essay Akhir', 'type' => 'essay']);
        $essayId = DB::table('essay_submissions')->insertGetId([
            'user_id' => $user->id,
            'content_id' => $essayContent->id,
            'status' => 'submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $essay = EssaySubmission::findOrFail($essayId);
        $essay->update(['status' => 'graded']);
        $essay->update(['graded_at' => now()]);

        $caseContent = Content::factory()->for($lesson)->create(['title' => 'Studi Kasus', 'type' => 'case_study']);
        $caseStudy = CaseStudySubmission::create([
            'user_id' => $user->id,
            'content_id' => $caseContent->id,
            'status' => 'submitted',
        ]);
        $caseStudy->update(['status' => 'graded']);

        $documentContent = Content::factory()->for($lesson)->create(['title' => 'Dokumen Praktik', 'type' => 'document']);
        $document = DocumentSubmission::create([
            'user_id' => $user->id,
            'content_id' => $documentContent->id,
            'attempt' => 1,
            'status' => 'submitted',
        ]);
        $document->update(['status' => 'failed']);

        Notification::assertSentToTimes($user, FinalAssessmentResultNotification::class, 3);
        Notification::assertSentTo($user, FinalAssessmentResultNotification::class, fn ($notification) => $notification->assessmentType === 'essay'
            && $notification->afterCommit === true
            && $notification->toMail($user)->actionUrl === route('essays.result', $essay)
        );
        Notification::assertSentTo($user, FinalAssessmentResultNotification::class, fn ($notification) => $notification->assessmentType === 'document'
            && $notification->status === 'failed'
            && $notification->attempt === 1
        );
    }

    public function test_export_completion_notification_keeps_database_channel_and_adds_secure_email_link(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'Course Export']);
        $export = ExportHistory::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'filter' => 'all',
            'status' => 'done',
            'file_path' => 'exports/test.xlsx',
        ]);
        $notification = new ExportCompletedNotification($export);

        $this->assertSame(['database', 'mail'], $notification->via($user));
        $this->assertTrue($notification->afterCommit);
        $this->assertSame($export->id, $notification->toDatabase($user)['export_id']);
        $this->assertSame(route('exports.download', $export), $notification->toMail($user)->actionUrl);
    }

    public function test_learning_reminders_are_deduplicated_and_respect_user_preference(): void
    {
        Notification::fake();
        $now = now()->startOfMinute();
        $this->travelTo($now);
        $course = Course::factory()->create(['title' => 'Course Live', 'status' => 'published']);
        $lesson = Lesson::factory()->for($course)->create();
        $content = Content::factory()->for($lesson)->create([
            'title' => 'Sesi Live',
            'type' => 'zoom',
            'is_scheduled' => true,
            'scheduled_start' => $now->copy()->addHours(2),
            'scheduled_end' => $now->copy()->addHours(3),
        ]);
        $enabled = User::factory()->create(['learning_reminder_email_enabled' => true]);
        $disabled = User::factory()->create(['learning_reminder_email_enabled' => false]);
        $course->participants()->attach([$enabled->id, $disabled->id]);

        $this->artisan('learning-reminders:send')->assertSuccessful();
        $this->artisan('learning-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($enabled, LearningReminderNotification::class, 1);
        Notification::assertNotSentTo($disabled, LearningReminderNotification::class);

        $this->travelTo($now->copy()->addMinutes(90));
        $this->artisan('learning-reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($enabled, LearningReminderNotification::class, 2);
        $this->assertDatabaseCount('learning_reminder_dispatches', 2);
        $this->assertDatabaseHas('learning_reminder_dispatches', [
            'content_id' => $content->id,
            'user_id' => $enabled->id,
            'reminder_type' => 'h_0',
        ]);
    }

    public function test_user_can_disable_learning_reminder_email_from_profile(): void
    {
        $user = User::factory()->create(['learning_reminder_email_enabled' => true]);

        $this->actingAs($user)
            ->patch(route('profile.email-preferences.update'), [
                'learning_reminder_email_enabled' => '0',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertFalse($user->fresh()->learning_reminder_email_enabled);
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeText('Preferensi Email');
    }
}
