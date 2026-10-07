<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseSalesProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_and_update_a_course_sales_profile(): void
    {
        $manager = $this->manager();
        $course = Course::factory()->create([
            'title' => 'Dasar Bermain Bass',
            'status' => 'published',
            'program_type' => 'regular',
        ]);

        $response = $this->actingAs($manager)->put(route('admin.course-commerce.update', $course), [
            'visibility' => 'catalog',
            'price' => 250000,
            'requires_payment_verification' => 0,
            'sales_profile' => [
                'sales_status' => 'published',
                'headline' => 'Kuasai teknik dasar bass dengan fondasi yang benar',
                'target_audience' => 'Pemain bass pemula',
                'learning_benefits' => "Memahami ritme\nMemainkan lagu pertama",
                'requirements' => 'Memiliki alat musik bass',
                'level' => 'beginner',
                'estimated_duration_minutes' => 480,
                'language' => 'Bahasa Indonesia',
                'promo_video_url' => 'https://example.com/promo',
                'faq' => [
                    ['question' => 'Apakah cocok untuk pemula?', 'answer' => 'Ya, dimulai dari dasar.'],
                ],
                'seo_title' => 'Course Bass untuk Pemula',
                'seo_description' => 'Pelajari teknik dasar bass secara terstruktur.',
            ],
        ]);

        $response->assertRedirect(route('admin.course-commerce.index'));

        $profile = $course->refresh()->salesProfile;
        $this->assertNotNull($profile);
        $this->assertSame('dasar-bermain-bass', $profile->slug);
        $this->assertSame('published', $profile->sales_status);
        $this->assertNotNull($profile->published_at);
        $this->assertSame('Apakah cocok untuk pemula?', $profile->faq[0]['question']);
        $this->assertTrue($course->isInCatalog());

        $this->get(route('admin.course-commerce.index'))
            ->assertOk()
            ->assertSeeText('Sales')
            ->assertSeeText('Penjualan Course');
        $this->get(route('admin.course-commerce.edit', $course))->assertOk();

        $this->get(route('shop.show', $course))
            ->assertOk()
            ->assertSee('Kuasai teknik dasar bass dengan fondasi yang benar')
            ->assertSee('Apakah cocok untuk pemula?');
    }

    public function test_non_published_sales_profile_is_hidden_but_legacy_catalog_course_remains_visible(): void
    {
        $legacyCourse = Course::factory()->create([
            'title' => 'Course Lama',
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $hiddenCourse = Course::factory()->create([
            'title' => 'Course Disembunyikan',
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $hiddenCourse->salesProfile()->create([
            'slug' => 'course-disembunyikan',
            'sales_status' => 'hidden',
        ]);

        $this->assertTrue($legacyCourse->isInCatalog());
        $this->assertFalse($hiddenCourse->refresh()->isInCatalog());

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertSee('Course Lama')
            ->assertDontSee('Course Disembunyikan');
        $this->get(route('shop.show', $hiddenCourse))->assertNotFound();
    }

    public function test_sales_profile_slug_must_be_unique(): void
    {
        $manager = $this->manager();
        $existing = Course::factory()->create();
        $existing->salesProfile()->create([
            'slug' => 'course-bass',
            'sales_status' => 'published',
        ]);
        $course = Course::factory()->create();

        $this->actingAs($manager)->put(route('admin.course-commerce.update', $course), [
            'visibility' => 'catalog',
            'price' => 100000,
            'requires_payment_verification' => 0,
            'sales_profile' => [
                'slug' => 'course-bass',
                'sales_status' => 'draft',
            ],
        ])->assertSessionHasErrors('sales_profile.slug');

        $this->assertNull($course->refresh()->salesProfile);
    }

    public function test_avpn_course_cannot_be_managed_or_exposed_through_commerce(): void
    {
        $manager = $this->manager();
        $course = Course::factory()->create([
            'title' => 'Program AVPN',
            'status' => 'published',
            'visibility' => 'catalog',
            'price' => 100000,
            'program_type' => 'avpn_ai',
        ]);

        $this->actingAs($manager)
            ->get(route('admin.course-commerce.index'))
            ->assertOk()
            ->assertDontSee('Program AVPN');
        $this->get(route('admin.course-commerce.edit', $course))->assertNotFound();
        $this->put(route('admin.course-commerce.update', $course), [
            'visibility' => 'catalog',
            'price' => 100000,
            'requires_payment_verification' => 0,
            'sales_profile' => ['sales_status' => 'published'],
        ])->assertForbidden();

        $this->assertFalse($course->isInCatalog());
        $this->get(route('shop.show', $course))->assertNotFound();
        $this->get(route('checkout.choose', $course))->assertNotFound();
    }

    public function test_course_manager_without_commerce_permission_cannot_manage_sales(): void
    {
        Permission::findOrCreate('manage all courses');
        $courseManager = User::factory()->create();
        $courseManager->givePermissionTo('manage all courses');
        $course = Course::factory()->create();

        $this->actingAs($courseManager)
            ->get(route('admin.course-commerce.index'))
            ->assertForbidden();
        $this->get(route('admin.course-commerce.edit', $course))->assertForbidden();
    }

    private function manager(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage course commerce');

        $user = User::factory()->create();
        $user->givePermissionTo('manage course commerce');

        return $user;
    }
}
