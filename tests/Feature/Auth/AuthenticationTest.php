<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_can_view_public_homepage(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_guest_admin_routes_redirect_to_admin_login(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect('/admin/login');
    }

    public function test_guest_member_routes_redirect_to_member_login(): void
    {
        $this->get('/member/dashboard')
            ->assertRedirect('/member/login');
    }

    public function test_admin_can_log_in_and_reach_admin_dashboard(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
        ])->assignRole(AccessControl::ROLE_ADMIN);

        $this->post('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticated();
        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_member_can_log_in_and_reach_member_dashboard(): void
    {
        User::factory()->create([
            'email' => 'member@example.test',
            'password' => 'password',
        ])->assignRole(AccessControl::ROLE_MEMBER);

        $this->post('/member/login', [
            'email' => 'member@example.test',
            'password' => 'password',
        ])->assertRedirect('/member/dashboard');

        $this->assertAuthenticated();
        $this->get('/member/dashboard')->assertOk();
    }

    public function test_member_cannot_access_admin_area(): void
    {
        $member = User::factory()->create();
        $member->assignRole(AccessControl::ROLE_MEMBER);

        $this->actingAs($member)
            ->get('/admin/dashboard')
            ->assertRedirect('/member/dashboard');
    }

    public function test_admin_cannot_access_member_area(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole(AccessControl::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get('/member/dashboard')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_legacy_member_role_does_not_bounce_between_areas(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $this->actingAs($member)
            ->get('/admin/dashboard')
            ->assertRedirect('/member/dashboard');
    }

    public function test_legacy_admin_role_does_not_bounce_between_areas(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/member/dashboard')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_legacy_admin_area_roles_are_treated_as_admin_users(): void
    {
        $supportStaff = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_SUPPORT_STAFF,
        ]);

        $this->actingAs($supportStaff)
            ->get('/member/dashboard')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_admin_permission_routes_are_enforced(): void
    {
        $cmsManager = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_CMS_MANAGER,
        ]);
        $cmsManager->assignRole(AccessControl::ROLE_CMS_MANAGER);

        $this->actingAs($cmsManager)
            ->get('/admin/pages')
            ->assertOk();

        $this->actingAs($cmsManager)
            ->get('/admin/members')
            ->assertForbidden();
    }

    public function test_membership_manager_cannot_access_cms_routes(): void
    {
        $membershipManager = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_MEMBERSHIP_MANAGER,
        ]);
        $membershipManager->assignRole(AccessControl::ROLE_MEMBERSHIP_MANAGER);

        $this->actingAs($membershipManager)
            ->get('/admin/members')
            ->assertOk();

        $this->actingAs($membershipManager)
            ->get('/admin/pages')
            ->assertForbidden();
    }

    public function test_admin_navigation_is_filtered_by_permissions(): void
    {
        $supportStaff = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_SUPPORT_STAFF,
        ]);
        $supportStaff->assignRole(AccessControl::ROLE_SUPPORT_STAFF);

        $this->actingAs($supportStaff)
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Dashboard', false)
                ->where('navigation.admin.0.label', 'Papan Pemuka')
                ->where('navigation.admin.1.label', 'Pengumuman')
                ->where('navigation.admin.2.label', 'Aduan')
                ->missing('navigation.admin.3')
            );
    }

    public function test_member_routes_require_member_permission(): void
    {
        $member = User::factory()->create();
        $member->assignRole(AccessControl::ROLE_MEMBER);

        $this->actingAs($member)
            ->get('/member/profile')
            ->assertOk();

        $member->syncRoles([]);

        $this->actingAs($member)
            ->get('/member/profile')
            ->assertForbidden();
    }
}
