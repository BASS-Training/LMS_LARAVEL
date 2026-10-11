<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_guest_layouts_enable_page_transitions(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('data-page-transitions', false)
            ->assertSee('page-transition-content', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-page-transitions', false)
            ->assertSee('page-transition-content', false);
    }

    public function test_course_pages_enable_page_transitions_for_non_admin_users(): void
    {
        Permission::findOrCreate('view courses', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('view courses');

        $this->actingAs($user)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('data-page-transitions', false)
            ->assertSee('page-transition-content', false);
    }

    public function test_global_stylesheet_defines_page_entrance_transition(): void
    {
        $stylesheet = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($stylesheet);
        $this->assertStringContainsString('animation: page-content-enter', $stylesheet);
        $this->assertStringNotContainsString('opacity: 0', $stylesheet);
        $this->assertStringNotContainsString('@view-transition', $stylesheet);
        $this->assertStringNotContainsString('view-transition-name', $stylesheet);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $stylesheet);
    }
}
