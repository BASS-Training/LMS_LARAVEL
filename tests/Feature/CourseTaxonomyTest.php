<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourseTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_taxonomy_relations_and_deletion_preserve_courses(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $tag = Tag::factory()->create();
        $course = Course::factory()->create();

        $course->categories()->attach([$parent->id, $child->id]);
        $course->tags()->attach($tag);

        $this->assertCount(2, $course->refresh()->categories);
        $this->assertTrue($course->tags->contains($tag));

        $parent->delete();
        $tag->delete();

        $this->assertModelExists($course);
        $this->assertNull($child->refresh()->parent_id);
        $this->assertDatabaseMissing('course_tag', ['course_id' => $course->id, 'tag_id' => $tag->id]);
    }

    public function test_slug_is_unique_and_stable_after_rename(): void
    {
        $first = Category::factory()->create(['name' => 'Data Science']);
        $second = Category::factory()->create(['name' => 'Data Science']);

        $this->assertSame('data-science', $first->slug);
        $this->assertSame('data-science-2', $second->slug);

        $first->update(['name' => 'Analitik Data']);

        $this->assertSame('data-science', $first->refresh()->slug);
    }

    public function test_course_duplication_copies_taxonomy_assignments(): void
    {
        $course = Course::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $course->categories()->attach($category);
        $course->tags()->attach($tag);

        $copy = $course->duplicate();

        $this->assertTrue($copy->categories()->whereKey($category->id)->exists());
        $this->assertTrue($copy->tags()->whereKey($tag->id)->exists());
    }

    public function test_course_update_syncs_taxonomy(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage all courses']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage all courses');
        $course = Course::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)->patch(route('courses.update', $course), [
            'title' => 'Course Taxonomy',
            'status' => 'published',
            'program_type' => 'regular',
            'category_ids' => [$category->id],
            'tag_ids' => [$tag->id],
            'taxonomy_present' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('category_course', ['course_id' => $course->id, 'category_id' => $category->id]);
        $this->assertDatabaseHas('course_tag', ['course_id' => $course->id, 'tag_id' => $tag->id]);
    }

    public function test_course_update_preserves_omitted_taxonomy_and_can_explicitly_clear_it(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage all courses']);
        $user = User::factory()->create();
        $user->givePermissionTo('manage all courses');
        $course = Course::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $course->categories()->attach($category);
        $course->tags()->attach($tag);

        $baseData = [
            'title' => 'Course Taxonomy',
            'status' => 'published',
            'program_type' => 'regular',
        ];

        $this->actingAs($user)->patch(route('courses.update', $course), $baseData)->assertRedirect();
        $this->assertTrue($course->categories()->whereKey($category->id)->exists());
        $this->assertTrue($course->tags()->whereKey($tag->id)->exists());

        $this->actingAs($user)->patch(route('courses.update', $course), $baseData + ['taxonomy_present' => 1])->assertRedirect();
        $this->assertFalse($course->categories()->exists());
        $this->assertFalse($course->tags()->exists());
    }
}
