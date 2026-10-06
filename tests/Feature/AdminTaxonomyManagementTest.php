<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminTaxonomyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'manage course taxonomy']);
        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo('manage course taxonomy');
    }

    public function test_user_without_permission_cannot_manage_taxonomy(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_manager_can_create_update_and_delete_category(): void
    {
        $this->actingAs($this->manager)->post(route('admin.categories.store'), [
            'name' => 'Pemrograman',
            'description' => 'Course pemrograman',
            'sort_order' => 2,
            'is_active' => 1,
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'pemrograman')->firstOrFail();

        $this->actingAs($this->manager)->put(route('admin.categories.update', $category), [
            'name' => 'Pengembangan Perangkat Lunak',
            'sort_order' => 1,
            'is_active' => 0,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertSame('pemrograman', $category->refresh()->slug);
        $this->assertFalse($category->is_active);

        $this->actingAs($this->manager)->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertModelMissing($category);
    }

    public function test_category_parent_cycle_is_rejected(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->manager)->put(route('admin.categories.update', $parent), [
            'name' => $parent->name,
            'parent_id' => $child->id,
            'sort_order' => 0,
            'is_active' => 1,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->refresh()->parent_id);
    }

    public function test_manager_can_create_and_update_tag_with_stable_slug(): void
    {
        $this->actingAs($this->manager)->post(route('admin.tags.store'), [
            'name' => 'Pemula',
            'is_active' => 1,
        ])->assertRedirect(route('admin.tags.index'));

        $tag = Tag::where('slug', 'pemula')->firstOrFail();
        $this->actingAs($this->manager)->put(route('admin.tags.update', $tag), [
            'name' => 'Tingkat Dasar',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame('pemula', $tag->refresh()->slug);
    }
}
