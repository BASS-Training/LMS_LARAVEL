<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LearningPathFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_learning_path_requires_active_path_and_two_eligible_courses(): void
    {
        $courses = Course::factory()->count(2)->create([
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $path = LearningPath::factory()->create(['is_active' => true]);
        $path->courses()->attach([
            $courses[1]->id => ['sort_order' => 0],
            $courses[0]->id => ['sort_order' => 1],
        ]);

        $this->get(route('learning-paths.show', $path))
            ->assertOk()
            ->assertSeeText($path->title)
            ->assertSeeTextInOrder([$courses[1]->title, $courses[0]->title]);

        $courses[0]->update(['status' => 'draft']);
        $this->get(route('learning-paths.show', $path))->assertNotFound();

        $courses[0]->update(['status' => 'published']);
        $path->courses()->detach($courses[0]);
        $this->get(route('learning-paths.show', $path))->assertNotFound();
    }

    public function test_avpn_learning_path_is_only_visible_to_approved_users(): void
    {
        $courses = Course::factory()->count(2)->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'avpn_ai',
        ]);
        $path = LearningPath::factory()->create(['is_active' => true]);
        $path->courses()->attach($courses->pluck('id'));

        $this->get(route('learning-paths.show', $path))->assertNotFound();
        $this->actingAs(User::factory()->create(['avpn_verification_status' => 'pending']))
            ->get(route('learning-paths.show', $path))
            ->assertNotFound();
        $this->actingAs(User::factory()->create(['avpn_verification_status' => 'approved']))
            ->get(route('learning-paths.show', $path))
            ->assertOk();
    }

    public function test_personal_progress_reads_enrollment_without_changing_it(): void
    {
        $courses = Course::factory()->count(2)->create([
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $path = LearningPath::factory()->create(['is_active' => true]);
        $path->courses()->attach($courses->pluck('id'));
        $user = User::factory()->create();
        $user->courses()->attach($courses[0]);

        $this->actingAs($user)
            ->get(route('learning-paths.show', $path))
            ->assertOk()
            ->assertSeeText('Belum dimulai')
            ->assertSeeText('Belum terdaftar')
            ->assertSeeText('0%');

        $this->assertSame(1, $user->courses()->count());
        $this->assertFalse($user->courses()->whereKey($courses[1]->id)->exists());
    }

    public function test_admin_routes_require_permission_and_preserve_course_order(): void
    {
        Permission::findOrCreate('manage learning paths', 'web');
        $manager = User::factory()->create();
        $manager->givePermissionTo('manage learning paths');
        $courses = Course::factory()->count(2)->create([
            'status' => 'published',
            'visibility' => 'catalog',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.learning-paths.index'))
            ->assertForbidden();

        $this->actingAs($manager)->post(route('admin.learning-paths.store'), [
            'title' => 'Jalur Data Profesional',
            'slug' => '',
            'short_description' => 'Belajar data dari dasar.',
            'description' => 'Urutan course yang disarankan.',
            'is_active' => 1,
            'course_ids' => [$courses[1]->id, $courses[0]->id],
        ])->assertRedirect(route('admin.learning-paths.index'));

        $path = LearningPath::where('slug', 'jalur-data-profesional')->firstOrFail();
        $this->assertSame([$courses[1]->id, $courses[0]->id], $path->courses()->pluck('courses.id')->all());
        $this->assertSame(0, $path->courses()->first()->pivot->sort_order);
        $this->assertDatabaseCount('course_user', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_landing_and_catalog_only_show_eligible_learning_paths(): void
    {
        $courses = Course::factory()->count(2)->create([
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $visible = LearningPath::factory()->create(['title' => 'Jalur Publik', 'is_active' => true]);
        $visible->courses()->attach($courses->pluck('id'));
        $hidden = LearningPath::factory()->create(['title' => 'Jalur Nonaktif', 'is_active' => false]);
        $hidden->courses()->attach($courses->pluck('id'));

        $this->get(route('welcome'))
            ->assertOk()
            ->assertSeeText('Jalur Publik')
            ->assertDontSeeText('Jalur Nonaktif');

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSeeText('Jalur Publik')
            ->assertDontSeeText('Jalur Nonaktif');

        $this->get(route('shop.show', $courses[0]))
            ->assertOk()
            ->assertSeeText('Langkah 1 dari 2');
    }
}
