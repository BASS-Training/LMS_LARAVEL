<?php

namespace Tests\Feature\Certificates;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\CourseClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTrainingDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prioritizes_course_training_dates_for_training_date_range(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'training_start_date' => '2026-02-26',
            'training_end_date' => '2026-02-28',
        ]);
        $template = CertificateTemplate::create([
            'name' => 'Template Course Schedule',
            'layout_data' => [],
        ]);

        // Even if class schedule exists, certificate should use course-level training dates.
        $class = CourseClass::create([
            'course_id' => $course->id,
            'name' => 'Batch A',
            'start_date' => '2026-02-26 09:00:00',
            'end_date' => '2026-03-01 17:00:00',
            'status' => 'completed',
        ]);
        $class->participants()->attach($user->id);

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_template_id' => $template->id,
            'certificate_code' => Certificate::generateCertificateCode(),
            'issued_at' => '2026-03-01 08:00:00',
        ]);

        $this->assertSame('26 Februari 2026 - 28 Februari 2026', $certificate->getTrainingDateRangeLabel());
    }

    public function test_it_uses_participant_class_period_for_training_date_range(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $course = Course::factory()->create();
        $template = CertificateTemplate::create([
            'name' => 'Template A',
            'layout_data' => [],
        ]);

        $classForUser = CourseClass::create([
            'course_id' => $course->id,
            'name' => 'Batch A',
            'start_date' => '2026-03-06 09:00:00',
            'end_date' => '2026-03-08 17:00:00',
            'status' => 'completed',
        ]);

        $classForOther = CourseClass::create([
            'course_id' => $course->id,
            'name' => 'Batch B',
            'start_date' => '2026-04-01 09:00:00',
            'end_date' => '2026-04-03 17:00:00',
            'status' => 'completed',
        ]);

        $classForUser->participants()->attach($user->id);
        $classForOther->participants()->attach($otherUser->id);

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_template_id' => $template->id,
            'certificate_code' => Certificate::generateCertificateCode(),
            'issued_at' => '2026-03-14 10:00:00',
        ]);

        $this->assertSame('6 Maret 2026 - 8 Maret 2026', $certificate->getTrainingDateRangeLabel());
        $this->assertSame('6 Maret 2026', $certificate->getTrainingStartDateLabel());
        $this->assertSame('8 Maret 2026', $certificate->getTrainingEndDateLabel());
    }

    public function test_it_falls_back_to_course_class_closest_to_issued_date_when_user_class_is_missing(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $template = CertificateTemplate::create([
            'name' => 'Template B',
            'layout_data' => [],
        ]);

        CourseClass::create([
            'course_id' => $course->id,
            'name' => 'Batch Lama',
            'start_date' => '2025-06-01 09:00:00',
            'end_date' => '2025-06-03 17:00:00',
            'status' => 'completed',
        ]);

        CourseClass::create([
            'course_id' => $course->id,
            'name' => 'Batch Sertifikat',
            'start_date' => '2025-10-10 09:00:00',
            'end_date' => '2025-10-12 17:00:00',
            'status' => 'completed',
        ]);

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_template_id' => $template->id,
            'certificate_code' => Certificate::generateCertificateCode(),
            'issued_at' => '2025-10-14 08:00:00',
        ]);

        $this->assertSame('10 Oktober 2025 - 12 Oktober 2025', $certificate->getTrainingDateRangeLabel());
    }
}
