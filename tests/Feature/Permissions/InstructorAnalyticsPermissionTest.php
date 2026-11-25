<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstructorAnalyticsPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_reports_do_not_grant_analytics_access_after_role_permission_is_revoked(): void
    {
        Permission::findOrCreate('view progress reports', 'web');
        Permission::findOrCreate('view instructor analytics', 'web');
        $role = Role::findOrCreate('instructor', 'web');
        $role->syncPermissions(['view progress reports', 'view instructor analytics']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->can('view instructor analytics'));
        $role->syncPermissions(['view progress reports']);
        $user = $user->fresh();
        $this->assertFalse($user->can('view instructor analytics'));
        $this->assertTrue($user->can('view progress reports'));

        $this->actingAs($user);
        $this->get(route('instructor-analytics.index'))->assertForbidden();
        $this->get(route('instructor-analytics.compare'))->assertForbidden();
        $this->get(route('instructor-analytics.detail', $user))->assertForbidden();
    }
}
