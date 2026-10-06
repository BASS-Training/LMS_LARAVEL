<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseSaleFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('manage all courses');
        Permission::findOrCreate('manage own courses');
        Permission::findOrCreate('view courses');
    }

    public function test_sale_filter_matches_course_visibility_regardless_of_price_or_publication(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage all courses');

        $paid = Course::factory()->create(['title' => 'Kursus Berbayar', 'status' => 'published', 'visibility' => 'catalog', 'price' => 100000]);
        $free = Course::factory()->create(['title' => 'Kursus Gratis', 'visibility' => 'catalog', 'price' => 0]);
        $private = Course::factory()->create(['title' => 'Kursus Internal', 'visibility' => 'private']);

        $this->actingAs($admin)->get(route('courses.index'))
            ->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->total() === 3);

        $this->get(route('courses.index', ['penjualan' => 'catalog']))
            ->assertOk()
            ->assertViewHas('saleFilter', 'catalog')
            ->assertViewHas('courses', fn ($courses) => $courses->total() === 2
                && collect($courses->getCollection()->modelKeys())->sort()->values()->all() === collect([$free->id, $paid->id])->sort()->values()->all());

        $this->get(route('courses.index', ['penjualan' => 'private']))
            ->assertOk()
            ->assertViewHas('saleFilter', 'private')
            ->assertViewHas('courses', fn ($courses) => $courses->total() === 1
                && $courses->getCollection()->first()->is($private));
    }

    public function test_sale_filter_combines_with_search_and_stays_in_pagination_links(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage all courses');

        foreach (range(1, 7) as $number) {
            Course::factory()->create(['title' => "Bass Catalog {$number}", 'visibility' => 'catalog']);
        }
        Course::factory()->create(['title' => 'Bass Private', 'visibility' => 'private']);
        Course::factory()->create(['title' => 'Piano Catalog', 'visibility' => 'catalog']);

        $this->actingAs($admin)->get(route('courses.index', ['q' => 'Bass', 'penjualan' => 'catalog']))
            ->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->total() === 7
                && $courses->count() === 6
                && str_contains($courses->url(2), 'q=Bass')
                && str_contains($courses->url(2), 'penjualan=catalog'));

        $this->get(route('courses.index', ['q' => 'Bass', 'penjualan' => 'private']))
            ->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->total() === 1
                && $courses->getCollection()->first()->title === 'Bass Private');

        $this->get(route('courses.index', ['q' => 'Piano', 'penjualan' => 'private']))
            ->assertOk()
            ->assertSeeText('Tidak ada kursus tidak dijual yang cocok dengan kata kunci')
            ->assertSee('Reset');
    }

    public function test_sale_filter_keeps_instructor_access_scope_and_rejects_invalid_value(): void
    {
        $instructor = User::factory()->create();
        $instructor->givePermissionTo('view courses', 'manage own courses');
        $own = Course::factory()->create(['title' => 'Own Catalog', 'visibility' => 'catalog']);
        $own->instructors()->attach($instructor);
        Course::factory()->create(['title' => 'Other Catalog', 'visibility' => 'catalog']);

        $this->actingAs($instructor)->get(route('courses.index', ['penjualan' => 'catalog']))
            ->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->total() === 1
                && $courses->getCollection()->first()->is($own));

        $this->get(route('courses.index', ['penjualan' => 'unknown']))
            ->assertSessionHasErrors('penjualan');
    }
}
