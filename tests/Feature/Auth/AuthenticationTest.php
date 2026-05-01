<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

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
        ]);

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
        ]);

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

        $this->actingAs($member)
            ->get('/admin/dashboard')
            ->assertRedirect('/member/dashboard');
    }

    public function test_admin_cannot_access_member_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/member/dashboard')
            ->assertRedirect('/admin/dashboard');
    }
}
