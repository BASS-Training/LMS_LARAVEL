<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CoursePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_commerce_manager_can_select_previewable_content_in_curriculum_order(): void
    {
        $manager = $this->manager();
        $course = $this->catalogCourse();
        $lesson = Lesson::factory()->for($course)->create();
        $video = Content::factory()->for($lesson)->create(['type' => 'video', 'order' => 1]);
        $text = Content::factory()->for($lesson)->create(['type' => 'text', 'order' => 2]);

        $this->actingAs($manager)
            ->put(route('admin.course-commerce.update', $course), $this->commercePayload([
                $video->id,
                $text->id,
            ]))
            ->assertRedirect(route('admin.course-commerce.index'));

        $this->assertDatabaseHas('course_previews', [
            'course_id' => $course->id,
            'content_id' => $video->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('course_previews', [
            'course_id' => $course->id,
            'content_id' => $text->id,
            'sort_order' => 1,
        ]);
    }

    public function test_preview_selection_rejects_unsupported_or_foreign_content(): void
    {
        $manager = $this->manager();
        $course = $this->catalogCourse();
        $lesson = Lesson::factory()->for($course)->create();
        $quiz = Content::factory()->for($lesson)->create(['type' => 'quiz']);
        $foreign = Content::factory()->create(['type' => 'text']);

        $this->actingAs($manager)
            ->from(route('admin.course-commerce.edit', $course))
            ->put(route('admin.course-commerce.update', $course), $this->commercePayload([
                $quiz->id,
                $foreign->id,
            ]))
            ->assertRedirect(route('admin.course-commerce.edit', $course))
            ->assertSessionHasErrors('preview_content_ids');

        $this->assertDatabaseCount('course_previews', 0);
    }

    public function test_public_preview_displays_selected_content_without_writing_progress(): void
    {
        $course = $this->catalogCourse();
        $lesson = Lesson::factory()->for($course)->create(['title' => 'Fondasi']);
        $content = Content::factory()->for($lesson)->create([
            'title' => 'Posisi Tangan',
            'type' => 'text',
            'body' => '<p>Materi preview lengkap.</p>',
        ]);
        $course->previews()->create(['content_id' => $content->id, 'sort_order' => 0]);

        $this->get(route('shop.show', $course))
            ->assertOk()
            ->assertSee(route('shop.preview', [$course, $content]))
            ->assertDontSee('Materi preview lengkap.');

        $this->get(route('shop.preview', [$course, $content]))
            ->assertOk()
            ->assertSee('Materi preview lengkap.');

        $this->assertDatabaseCount('content_user', 0);
        $this->assertDatabaseCount('lesson_user', 0);
    }

    public function test_public_preview_returns_not_found_for_unselected_or_hidden_course_content(): void
    {
        $course = $this->catalogCourse();
        $lesson = Lesson::factory()->for($course)->create();
        $selected = Content::factory()->for($lesson)->create();
        $unselected = Content::factory()->for($lesson)->create();
        $course->previews()->create(['content_id' => $selected->id, 'sort_order' => 0]);

        $this->get(route('shop.preview', [$course, $unselected]))->assertNotFound();

        $course->update(['visibility' => 'private']);
        $this->get(route('shop.preview', [$course, $selected]))->assertNotFound();
    }

    public function test_mobile_catalog_marks_previews_and_returns_safe_type_specific_payloads(): void
    {
        $course = $this->catalogCourse();
        $lesson = Lesson::factory()->for($course)->create(['title' => 'Pengenalan']);
        $text = Content::factory()->for($lesson)->create([
            'type' => 'text',
            'body' => '<p>Isi teks.</p>',
            'order' => 1,
        ]);
        $video = Content::factory()->for($lesson)->create([
            'type' => 'video',
            'body' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'order' => 2,
        ]);
        $image = Content::factory()->for($lesson)->create([
            'type' => 'image',
            'file_path' => 'contents/preview.jpg',
            'order' => 3,
        ]);
        $unselected = Content::factory()->for($lesson)->create(['type' => 'text', 'order' => 4]);

        foreach ([$text, $video, $image] as $index => $content) {
            $course->previews()->create(['content_id' => $content->id, 'sort_order' => $index]);
        }

        $this->getJson("/api/mobile/catalog/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.sections.0.lessons.0.isPreview', true)
            ->assertJsonPath('data.sections.0.lessons.3.isPreview', false)
            ->assertJsonPath('data.sections.0.lessons.3.previewUrl', null)
            ->assertJsonMissing(['body' => $unselected->body]);

        $this->getJson("/api/mobile/catalog/{$course->id}/preview/{$text->id}")
            ->assertOk()
            ->assertJsonPath('data.bodyHtml', '<p>Isi teks.</p>')
            ->assertJsonMissingPath('data.videoUrl')
            ->assertJsonMissingPath('data.images');

        $this->getJson("/api/mobile/catalog/{$course->id}/preview/{$video->id}")
            ->assertOk()
            ->assertJsonPath('data.embedUrl', 'https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=0&modestbranding=1&rel=0&color=white')
            ->assertJsonMissingPath('data.bodyHtml');

        $this->getJson("/api/mobile/catalog/{$course->id}/preview/{$image->id}")
            ->assertOk()
            ->assertJsonPath('data.images.0', url('/storage/contents/preview.jpg'))
            ->assertJsonMissingPath('data.bodyHtml');
    }

    private function catalogCourse(): Course
    {
        return Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
        ]);
    }

    private function manager(): User
    {
        Permission::findOrCreate('manage course commerce');
        $manager = User::factory()->create();
        $manager->givePermissionTo('manage course commerce');

        return $manager;
    }

    private function commercePayload(array $previewContentIds): array
    {
        return [
            'visibility' => 'catalog',
            'price' => 100000,
            'requires_payment_verification' => 0,
            'preview_content_ids' => $previewContentIds,
            'sales_profile' => ['sales_status' => 'published'],
        ];
    }
}
