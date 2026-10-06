<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Content;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'api_token' => 'catalog-test-token',
            'avpn_verification_status' => 'not_required',
        ]);
    }

    public function test_catalog_is_public_without_authentication(): void
    {
        $this->getJson('/api/mobile/catalog')
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_catalog_rejects_an_invalid_bearer_token(): void
    {
        $this->withToken('invalid-catalog-token')
            ->getJson('/api/mobile/catalog')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_catalog_only_returns_visible_courses_and_is_paginated(): void
    {
        $visibleCourses = Course::factory()->count(3)->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
        ]);

        Course::factory()->create(['status' => 'draft', 'visibility' => 'catalog']);
        Course::factory()->create(['status' => 'published', 'visibility' => 'private']);
        Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'avpn_ai',
        ]);

        $response = $this->getJson('/api/mobile/catalog?perPage=2');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.currentPage', 1)
            ->assertJsonPath('meta.pagination.perPage', 2)
            ->assertJsonPath('meta.pagination.total', 3)
            ->assertJsonPath('meta.pagination.lastPage', 2)
            ->assertJsonPath('meta.pagination.hasMorePages', true);

        $returnedIds = collect($response->json('data'))->pluck('id');
        $this->assertEmpty($returnedIds->diff($visibleCourses->pluck('id')->map(fn ($id) => (string) $id)));
    }

    public function test_catalog_search_and_price_filter_are_applied(): void
    {
        $freeCourse = Course::factory()->create([
            'title' => 'Dasar Bass Elektrik',
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
            'price' => null,
        ]);
        Course::factory()->create([
            'title' => 'Dasar Gitar Elektrik',
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
            'price' => 150000,
        ]);

        $this->withToken('catalog-test-token')
            ->getJson('/api/mobile/catalog?q=Bass&harga=free')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $freeCourse->id)
            ->assertJsonPath('data.0.isFree', true);
    }

    public function test_authenticated_catalog_includes_enrollment_status(): void
    {
        $course = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
        ]);
        $course->enrolledUsers()->attach($this->user);

        $this->withToken('catalog-test-token')
            ->getJson('/api/mobile/catalog')
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $course->id)
            ->assertJsonPath('data.0.isEnrolled', true);
    }

    public function test_avpn_catalog_detail_requires_an_approved_user(): void
    {
        $course = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'avpn_ai',
        ]);

        $this->getJson("/api/mobile/catalog/{$course->id}")
            ->assertNotFound();

        $this->user->update(['avpn_verification_status' => 'approved']);

        $this->withToken('catalog-test-token')
            ->getJson("/api/mobile/catalog/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', (string) $course->id);
    }

    public function test_catalog_filters_and_returns_active_taxonomy(): void
    {
        $category = Category::factory()->create(['name' => 'Teknologi']);
        $tag = Tag::factory()->create(['name' => 'Pemula']);
        $inactiveTag = Tag::factory()->create(['name' => 'Internal', 'is_active' => false]);
        $course = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
        ]);
        $course->categories()->attach($category);
        $course->tags()->attach([$tag->id, $inactiveTag->id]);

        $response = $this->withToken('catalog-test-token')
            ->getJson('/api/mobile/catalog?category='.$category->slug.'&tag='.$tag->slug);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.categories.0.slug', $category->slug)
            ->assertJsonPath('data.0.tags.0.slug', $tag->slug)
            ->assertJsonPath('meta.filters.categories.0.slug', $category->slug);

        $this->assertNotContains($inactiveTag->slug, collect($response->json('meta.filters.tags'))->pluck('slug'));
    }

    public function test_catalog_rejects_invalid_query_as_json(): void
    {
        $this->withToken('catalog-test-token')
            ->get('/api/mobile/catalog?q=a&perPage=51')
            ->assertUnprocessable()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Parameter katalog tidak valid.')
            ->assertJsonValidationErrors(['q', 'perPage']);
    }

    public function test_catalog_detail_only_contains_preview_content(): void
    {
        $course = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
        ]);
        $lesson = Lesson::factory()->for($course)->create();
        Content::factory()->for($lesson)->create([
            'title' => 'Teknik Slap',
            'body' => 'Materi rahasia yang tidak boleh tampil di preview.',
        ]);

        $response = $this->getJson("/api/mobile/catalog/{$course->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.sections.0.lessons.0.title', 'Teknik Slap')
            ->assertJsonMissing(['body' => 'Materi rahasia yang tidak boleh tampil di preview.']);

        $this->assertArrayNotHasKey('body', $response->json('data.sections.0.lessons.0'));
    }

    public function test_free_course_can_be_enrolled_but_paid_course_is_rejected(): void
    {
        $freeCourse = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
            'price' => 0,
        ]);
        $paidCourse = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
            'price' => 150000,
        ]);

        $this->withToken('catalog-test-token')
            ->postJson("/api/mobile/catalog/{$freeCourse->id}/daftar-gratis")
            ->assertOk()
            ->assertJsonPath('data.isEnrolled', true);

        $this->assertDatabaseHas('course_user', [
            'course_id' => $freeCourse->id,
            'user_id' => $this->user->id,
        ]);

        $this->withToken('catalog-test-token')
            ->postJson("/api/mobile/catalog/{$paidCourse->id}/daftar-gratis")
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $paidCourse->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_free_course_enrollment_still_requires_authentication(): void
    {
        $course = Course::factory()->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'program_type' => 'regular',
            'price' => 0,
        ]);

        $this->postJson("/api/mobile/catalog/{$course->id}/daftar-gratis")
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $this->user->id,
        ]);
    }
}
