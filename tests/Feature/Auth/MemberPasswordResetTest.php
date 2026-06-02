<?php

namespace Tests\Feature\Auth;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use App\Notifications\MemberPasswordReset;
use App\Enums\MemberStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MemberPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private Cooperative $cooperative;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cooperative = Cooperative::factory()->create(['slug' => 'demo-test-reset']);
    }

    public function test_forgot_password_page_exists(): void
    {
        $response = $this->get(route('member.password.request'));

        $response->assertOk();
    }

    public function test_reset_email_sent_for_member_user(): void
    {
        Notification::fake();

        $member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'membership_status' => MemberStatus::Active->value,
        ]);

        $user = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'email' => 'member@example.com',
            'role' => 'member',
            'user_type' => 'member',
            'status' => 'active',
        ]);

        $member->update(['user_id' => $user->id, 'portal_activated_at' => now()]);

        $response = $this->post(route('member.password.email'), [
            'email' => 'member@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Notification::assertSentTo($user, MemberPasswordReset::class);
    }

    public function test_safe_message_shown_even_if_email_does_not_exist(): void
    {
        $response = $this->post(route('member.password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_admin_email_should_not_trigger_member_reset(): void
    {
        Notification::fake();

        User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'email' => 'admin@example.com',
            'role' => 'admin',
            'user_type' => 'admin',
        ]);

        $response = $this->post(route('member.password.email'), [
            'email' => 'admin@example.com',
        ]);

        $response->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_reset_password_updates_password(): void
    {
        $user = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'email' => 'member@example.com',
            'role' => 'member',
            'user_type' => 'member',
            'password' => Hash::make('oldpassword'),
            'status' => 'active',
        ]);

        $token = app('auth.password.broker')->createToken($user);

        $response = $this->post(route('member.password.update'), [
            'token' => $token,
            'email' => 'member@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('member.login'));

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_reset_with_invalid_token_fails(): void
    {
        $response = $this->post(route('member.password.update'), [
            'token' => 'invalid-token',
            'email' => 'member@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}