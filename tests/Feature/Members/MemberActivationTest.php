<?php

namespace Tests\Feature\Members;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberActivationTest extends TestCase
{
    use RefreshDatabase;

    private Cooperative $cooperative;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create(['slug' => 'demo-test-activation']);

        $this->member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_no' => 'MBR-TEST-001',
            'full_name' => 'Ali bin Abu',
            'identity_no' => '900101-14-1234',
            'date_of_birth' => '1990-01-01',
            'membership_status' => MemberStatus::Active->value,
            'email' => null,
            'phone' => null,
            'user_id' => null,
            'portal_activated_at' => null,
        ]);
    }

    public function test_activation_page_exists(): void
    {
        $response = $this->get(route('member.activate'));

        $response->assertOk();
    }

    public function test_member_can_activate_account_with_valid_details(): void
    {
        $response = $this->post(route('member.activate.verify'), [
            'member_no' => 'MBR-TEST-001',
            'identity_no' => '900101-14-1234',
            'date_of_birth' => '1990-01-01',
        ]);

        $response->assertRedirect(route('member.activate'));

        $this->assertNotNull(session('activation_member_id'));

        $response2 = $this->post(route('member.activate.complete'), [
            'email' => 'ali@example.com',
            'phone' => '0123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response2->assertRedirect(route('member.login'));
        $response2->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'email' => 'ali@example.com',
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $this->member->refresh();
        $this->assertNotNull($this->member->user_id);
        $this->assertNotNull($this->member->portal_activated_at);
    }

    public function test_member_cannot_activate_with_wrong_identity_no(): void
    {
        $response = $this->post(route('member.activate.verify'), [
            'member_no' => 'MBR-TEST-001',
            'identity_no' => '999999-99-9999',
            'date_of_birth' => '1990-01-01',
        ]);

        $response->assertSessionHasErrors('member_no');
    }

    public function test_member_cannot_activate_with_wrong_date_of_birth(): void
    {
        $response = $this->post(route('member.activate.verify'), [
            'member_no' => 'MBR-TEST-001',
            'identity_no' => '900101-14-1234',
            'date_of_birth' => '2000-01-01',
        ]);

        $response->assertSessionHasErrors('date_of_birth');
    }

    public function test_already_activated_member_cannot_activate_again(): void
    {
        $user = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'email' => 'ali@example.com',
            'role' => 'member',
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $this->member->update([
            'user_id' => $user->id,
            'portal_activated_at' => now(),
        ]);

        $response = $this->post(route('member.activate.verify'), [
            'member_no' => 'MBR-TEST-001',
            'identity_no' => '900101-14-1234',
            'date_of_birth' => '1990-01-01',
        ]);

        $response->assertSessionHasErrors('member_no');
    }

    public function test_inactive_member_cannot_activate(): void
    {
        $this->member->update(['membership_status' => MemberStatus::Inactive->value]);

        $response = $this->post(route('member.activate.verify'), [
            'member_no' => 'MBR-TEST-001',
            'identity_no' => '900101-14-1234',
            'date_of_birth' => '1990-01-01',
        ]);

        $response->assertSessionHasErrors('member_no');
    }

    public function test_activation_creates_user_account(): void
    {
        $this->post(route('member.activate.verify'), [
            'member_no' => 'MBR-TEST-001',
            'identity_no' => '900101-14-1234',
            'date_of_birth' => '1990-01-01',
        ]);

        $this->post(route('member.activate.complete'), [
            'email' => 'ali.new@example.com',
            'phone' => '0123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ali.new@example.com',
            'cooperative_id' => $this->cooperative->id,
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $user = User::where('email', 'ali.new@example.com')->first();
        $this->assertTrue($user->hasRole(AccessControl::ROLE_MEMBER));

        $this->member->refresh();
        $this->assertEquals($user->id, $this->member->user_id);
    }

    public function test_activation_with_existing_email_fails(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'role' => 'admin',
            'user_type' => 'admin',
        ]);

        $this->post(route('member.activate.verify'), [
            'member_no' => 'MBR-TEST-001',
            'identity_no' => '900101-14-1234',
            'date_of_birth' => '1990-01-01',
        ]);

        $response = $this->post(route('member.activate.complete'), [
            'email' => 'existing@example.com',
            'phone' => '0123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}