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

    public function test_super_admin_role_is_treated_as_admin_user(): void
    {
        $superAdmin = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_SUPER_ADMIN,
        ]);
        $superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);

        $this->actingAs($superAdmin)
            ->get('/member/dashboard')
            ->assertRedirect('/admin/dashboard');
    }

    public function test_admin_permission_routes_are_enforced(): void
    {
        $admin = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin/pages')
            ->assertForbidden();

        $this->actingAs($admin)
            ->get('/admin/members')
            ->assertForbidden();
    }

    public function test_admin_with_targeted_permissions_cannot_access_ungranted_admin_routes(): void
    {
        $admin = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_ADMIN,
        ]);
        $admin->givePermissionTo([
            AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD,
            AccessControl::PERMISSION_VIEW_MEMBERS,
        ]);

        $this->actingAs($admin)
            ->get('/admin/members')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/pages')
            ->assertForbidden();
    }

    public function test_admin_navigation_is_filtered_by_permissions(): void
    {
        $admin = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_ADMIN,
        ]);
        $admin->givePermissionTo([
            AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD,
            AccessControl::PERMISSION_VIEW_ANNOUNCEMENTS,
            AccessControl::PERMISSION_VIEW_COMPLAINTS,
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Dashboard', false)
                ->where('navigation.admin.0.label', 'Papan Pemuka')
                ->where('navigation.admin.1.label', 'Pengurusan Kandungan')
                ->where('navigation.admin.2.label', 'Borang Online')
                ->where('navigation.admin.3.label', 'Aduan')
                ->missing('navigation.admin.4')
            );
    }

    public function test_borang_online_navigation_is_visible_for_lightweight_admin_role_without_form_permissions(): void
    {
        $admin = User::factory()->admin()->create([
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
            'status' => 'active',
        ]);
        $admin->givePermissionTo([
            AccessControl::PERMISSION_VIEW_ADMIN_DASHBOARD,
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Dashboard', false)
                ->where('navigation.admin.1.label', 'Borang Online')
                ->where('navigation.admin.1.href', route('admin.forms.index'))
                ->where('navigation.admin.1.icon', 'ClipboardList')
                ->missing('navigation.admin.1.children')
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