<?php

namespace Tests\Feature;

use App\Models\Bundle;
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
            ->assertSeeText('BASS Academy di Android')
            ->assertSeeText('Download di')
            ->assertSee('https://play.google.com/store/apps/details?id=com.basstraining.lms&amp;pcampaignid=web_share', false)
            ->assertSeeText('Program sedang dipersiapkan')
            ->assertDontSeeText('Paket belajar pilihan');
    }

    public function test_landing_page_highlights_the_three_latest_eligible_bundles(): void
    {
        $courses = Course::factory()->count(2)->create([
            'status' => 'published',
            'visibility' => 'catalog',
            'price' => 100000,
        ]);
        $visibleBundles = collect();

        foreach (range(1, 4) as $number) {
            $bundle = Bundle::factory()->create([
                'title' => "Paket Karier {$number}",
                'price' => 150000,
                'is_active' => true,
                'created_at' => now()->subDays(4 - $number),
            ]);
            $bundle->courses()->attach($courses->pluck('id'));
            $visibleBundles->push($bundle);
        }

        $inactiveBundle = Bundle::factory()->create([
            'title' => 'Paket Tidak Aktif',
            'is_active' => false,
        ]);
        $inactiveBundle->courses()->attach($courses->pluck('id'));

        $draftCourse = Course::factory()->create([
            'status' => 'draft',
            'visibility' => 'catalog',
            'price' => 100000,
        ]);
        $ineligibleBundle = Bundle::factory()->create([
            'title' => 'Paket Course Draft',
            'price' => 150000,
            'is_active' => true,
        ]);
        $ineligibleBundle->courses()->attach([$courses->first()->id, $draftCourse->id]);

        $this->get(route('welcome'))
            ->assertOk()
            ->assertViewHas('bundles', fn ($bundles) => $bundles->count() === 3
                && $bundles->first()->is($visibleBundles->last()))
            ->assertSeeText('Paket belajar pilihan')
            ->assertSeeText('Paket Karier 4')
            ->assertSeeText('Paket Karier 3')
            ->assertSeeText('Paket Karier 2')
            ->assertDontSeeText('Paket Karier 1')
            ->assertDontSeeText('Paket Tidak Aktif')
            ->assertDontSeeText('Paket Course Draft');
    }
}
