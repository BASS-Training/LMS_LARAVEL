<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_only_displays_courses_in_the_public_catalog(): void
    {
        $visibleCourse = Course::factory()->create([
            'title' => 'Pelatihan Kompetensi Profesional',
            'status' => 'published',
            'visibility' => 'catalog',
            'price' => 499000,
        ]);

        Course::factory()->create([
            'title' => 'Course Draft Rahasia',
            'status' => 'draft',
            'visibility' => 'catalog',
        ]);

        Course::factory()->create([
            'title' => 'Course Private Rahasia',
            'status' => 'published',
            'visibility' => 'private',
        ]);

        $this->get(route('welcome'))
            ->assertOk()
            ->assertViewIs('landing-page.index')
            ->assertViewHas('courses', fn ($courses) => $courses->contains($visibleCourse))
            ->assertSeeText('Pelatihan Kompetensi Profesional')
            ->assertDontSeeText('Course Draft Rahasia')
            ->assertDontSeeText('Course Private Rahasia');
    }

    public function test_landing_page_has_a_catalog_search_form_and_empty_state(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee(route('shop.index'), false)
            ->assertSee('name="q"', false)
            ->assertSeeText('Program sedang dipersiapkan');
    }
}
