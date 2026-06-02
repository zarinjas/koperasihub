<?php

namespace Tests\Feature\Admin;

use App\Models\Cooperative;
use App\Models\Unit;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitsAndStaffTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $superAdmin;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create(['status' => 'active']);

        $this->superAdmin = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_SUPER_ADMIN,
            'staff_id' => 'ST-00001',
        ]);
        $this->superAdmin->syncRoles([AccessControl::ROLE_SUPER_ADMIN]);

        $this->admin = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
            'staff_id' => 'ST-00002',
            'unit_id' => null,
        ]);
        $this->admin->syncRoles([AccessControl::ROLE_ADMIN]);

        $this->member = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $this->member->syncRoles([AccessControl::ROLE_MEMBER]);
    }

    public function test_super_admin_can_view_units_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/units')
            ->assertOk();
    }

    public function test_super_admin_can_create_unit(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/admin/units', [
                'name' => 'Unit Pentadbiran',
                'is_active' => true,
            ])
            ->assertRedirect('/admin/units');

        $this->assertDatabaseHas('units', ['name' => 'Unit Pentadbiran', 'cooperative_id' => $this->cooperative->id]);
    }

    public function test_super_admin_can_edit_unit(): void
    {
        $unit = Unit::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Asal',
            'slug' => 'unit-asal',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch("/admin/units/{$unit->id}", [
                'name' => 'Unit Dikemas Kini',
                'is_active' => true,
            ])
            ->assertRedirect('/admin/units');

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'Unit Dikemas Kini']);
    }

    public function test_admin_cannot_create_unit(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/units', [
                'name' => 'Unit Haram',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_edit_unit(): void
    {
        $unit = Unit::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Asal',
            'slug' => 'unit-asal',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/units/{$unit->id}", [
                'name' => 'Unit Haram',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_view_staff_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/staff')
            ->assertOk();
    }

    public function test_super_admin_can_create_staff_with_unit(): void
    {
        $unit = Unit::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit IT',
            'slug' => 'unit-it',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->post('/admin/staff', [
                'name' => 'Ahmad Shah',
                'email' => 'ahmad@koperasi.test',
                'staff_id' => 'ST-00100',
                'unit_id' => $unit->id,
                'position_title' => 'Pegawai IT',
                'role' => 'admin',
                'password' => 'password123',
            ])
            ->assertRedirect('/admin/staff');

        $this->assertDatabaseHas('users', [
            'name' => 'Ahmad Shah',
            'email' => 'ahmad@koperasi.test',
            'staff_id' => 'ST-00100',
            'unit_id' => $unit->id,
            'position_title' => 'Pegawai IT',
            'role' => 'admin',
        ]);
    }

    public function test_admin_cannot_access_staff_management(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/staff')
            ->assertForbidden();
    }

    public function test_member_cannot_access_staff_management(): void
    {
        $this->actingAs($this->member)
            ->get('/admin/staff')
            ->assertRedirect('/member/dashboard');
    }

    public function test_staff_id_is_required_for_admin_user_creation(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/admin/staff', [
                'name' => 'Tanpa ID',
                'email' => 'tanpaid@koperasi.test',
                'staff_id' => '',
                'role' => 'admin',
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('staff_id');
    }

    public function test_member_user_does_not_require_staff_id(): void
    {
        $memberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
            'staff_id' => null,
            'unit_id' => null,
        ]);

        $this->assertNull($memberUser->staff_id);
        $this->assertNull($memberUser->unit_id);
    }

    public function test_seeded_units_exist(): void
    {
        Unit::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Peruncitan',
            'slug' => 'unit-peruncitan',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->assertDatabaseHas('units', ['name' => 'Unit Peruncitan', 'cooperative_id' => $this->cooperative->id]);
    }
}