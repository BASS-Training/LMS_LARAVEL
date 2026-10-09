<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_capability_displays_sidebar_with_authorized_links(): void
    {
        Permission::findOrCreate('manage users', 'web');
        $admin = User::factory()->create();
        $admin->givePermissionTo('manage users');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-admin-sidebar', false)
            ->assertSee('data-page-transitions', false)
            ->assertSee('.admin-sidebar-desktop {', false)
            ->assertSee("localStorage.getItem('admin-sidebar-collapsed')", false)
            ->assertSee("classList.add('admin-sidebar-collapsed')", false)
            ->assertSeeText('Dashboard Admin')
            ->assertSeeText('Landing Page')
            ->assertSeeText('Katalog Course')
            ->assertSeeText('Manajemen Pengguna')
            ->assertSeeText('Analitik Peserta')
            ->assertDontSeeText('Verifikasi Pembayaran')
            ->assertDontSeeText('Manajemen Refund');
    }

    public function test_non_admin_capabilities_keep_the_existing_layout_without_sidebar(): void
    {
        Permission::findOrCreate('attempt quizzes', 'web');
        Permission::findOrCreate('view progress reports', 'web');

        $participant = User::factory()->create();
        $participant->givePermissionTo('attempt quizzes');
        $this->actingAs($participant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-admin-sidebar', false)
            ->assertSee('data-page-transitions', false);

        $eventOrganizer = User::factory()->create();
        $eventOrganizer->givePermissionTo('view progress reports');
        $this->actingAs($eventOrganizer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-admin-sidebar', false);
    }

    public function test_super_admin_sidebar_includes_transaction_controls(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-admin-sidebar', false)
            ->assertSeeText('Verifikasi Pembayaran')
            ->assertSeeText('Manajemen Refund');
    }
}
