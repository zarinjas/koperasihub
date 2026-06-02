<?php

namespace Tests\Feature\Admin;

use App\Models\Cooperative;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosterAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_still_access_posters_when_role_permissions_are_stale(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cooperative = Cooperative::factory()->create(['status' => 'active']);

        $superAdmin = User::factory()->create([
            'cooperative_id' => $cooperative->id,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_SUPER_ADMIN,
        ]);
        $superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);

        $superAdminRole = Role::findByName(AccessControl::ROLE_SUPER_ADMIN);
        $superAdminRole->revokePermissionTo([
            AccessControl::PERMISSION_VIEW_POSTERS,
            AccessControl::PERMISSION_CREATE_POSTERS,
            AccessControl::PERMISSION_EDIT_POSTERS,
            AccessControl::PERMISSION_DELETE_POSTERS,
            AccessControl::PERMISSION_PUBLISH_POSTERS,
        ]);

        $this->actingAs($superAdmin)
            ->get('/admin/posters')
            ->assertOk();
    }
}