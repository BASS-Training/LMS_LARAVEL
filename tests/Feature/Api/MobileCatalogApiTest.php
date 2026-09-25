<?php

namespace Tests\Feature\Api;

use App\Models\Content;
use App\Models\Course;
use App\Models\Lesson;
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

    public function test_catalog_requires_authentication(): void
    {
        $this->getJson('/api/mobile/catalog')
            ->assertUnauthorized()
            ->assertJson([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ]);
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

        $response = $this->withToken('catalog-test-token')
            ->getJson('/api/mobile/catalog?perPage=2');

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

        $response = $this->withToken('catalog-test-token')
            ->getJson("/api/mobile/catalog/{$course->id}");

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
}
