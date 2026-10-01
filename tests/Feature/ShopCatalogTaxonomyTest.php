<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCatalogTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_courses_by_active_category_and_tag(): void
    {
        $category = Category::factory()->create(['name' => 'Teknologi']);
        $tag = Tag::factory()->create(['name' => 'Pemula']);
        $matching = Course::factory()->create([
            'title' => 'Course Cocok',
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $other = Course::factory()->create([
            'title' => 'Course Lain',
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $matching->categories()->attach($category);
        $matching->tags()->attach($tag);

        $this->get(route('shop.index', ['category' => $category->slug, 'tag' => $tag->slug]))
            ->assertOk()
            ->assertSee('Course Cocok')
            ->assertDontSee('Course Lain');

        $this->assertModelExists($other);
    }

    public function test_inactive_taxonomy_is_not_exposed_or_accepted_as_filter(): void
    {
        $inactive = Category::factory()->create(['name' => 'Rahasia', 'is_active' => false]);
        $course = Course::factory()->create([
            'title' => 'Course Publik',
            'status' => 'published',
            'visibility' => 'catalog',
        ]);
        $course->categories()->attach($inactive);

        $this->get(route('shop.index'))->assertOk()->assertDontSee('Rahasia');
        $this->get(route('shop.index', ['category' => $inactive->slug]))
            ->assertSessionHasErrors('category');
    }
}
