<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberDigitalCardTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $memberUser;

    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create([
            'name' => 'Koperasi Demo Berhad',
            'short_name' => 'KDB',
            'status' => 'active',
        ]);

        $this->memberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $this->memberUser->assignRole(AccessControl::ROLE_MEMBER);

        $this->member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $this->memberUser->id,
            'member_no' => 'MBR-20260504-DEMO',
            'full_name' => 'Ahmad Zaim Bin Harun',
            'identity_no' => '900101015555',
            'email' => 'ahmad@example.test',
            'phone' => '0123456789',
            'address_line_1' => 'No. 8, Jalan Melur',
            'profile_photo_path' => 'member-photos/ahmad.png',
            'membership_status' => MemberStatus::Active->value,
        ]);
    }

    public function test_card_token_is_generated_for_member(): void
    {
        $member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'card_public_token' => null,
            'card_token_generated_at' => null,
        ]);

        $this->assertNotNull($member->card_public_token);
        $this->assertNotNull($member->card_token_generated_at);
        $this->assertSame(48, strlen($member->card_public_token));
    }

    public function test_member_can_view_own_card_page(): void
    {
        $this->actingAs($this->memberUser)
            ->get('/member/card')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Card', false)
                ->where('card.full_name', 'Ahmad Zaim Bin Harun')
                ->where('card.member_no', 'MBR-20260504-DEMO')
                ->where('card.readiness.has_profile_photo', true)
            );
    }

    public function test_member_cannot_view_another_member_card(): void
    {
        $otherUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $otherUser->assignRole(AccessControl::ROLE_MEMBER);

        $otherMember = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($this->memberUser)
            ->get("/member/card/{$otherMember->id}")
            ->assertForbidden();
    }

    public function test_public_verification_page_works_with_valid_token(): void
    {
        $this->get("/verify/member/{$this->member->card_public_token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Pages/Verify/MemberCard', false)
                ->where('isValid', true)
                ->where('verification.member.full_name', 'Ahmad Zaim Bin Harun')
                ->where('verification.member.member_no', 'MBR-20260504-DEMO')
                ->where('verification.member.membership_status', 'active')
            );
    }

    public function test_invalid_token_shows_safe_invalid_state(): void
    {
        $this->get('/verify/member/token-tidak-sah')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Pages/Verify/MemberCard', false)
                ->where('isValid', false)
                ->where('verification', null)
            );
    }

    public function test_public_verification_does_not_expose_sensitive_fields(): void
    {
        $this->get("/verify/member/{$this->member->card_public_token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('verification.member', fn (Assert $member) => $member
                    ->where('full_name', 'Ahmad Zaim Bin Harun')
                    ->missing('identity_no')
                    ->missing('address')
                    ->missing('phone')
                    ->missing('email')
                    ->missing('documents')
                    ->etc()
                )
            );
    }

    public function test_inactive_or_suspended_member_shows_inactive_verification_state(): void
    {
        $this->member->update([
            'membership_status' => MemberStatus::Suspended->value,
        ]);

        $this->get("/verify/member/{$this->member->fresh()->card_public_token}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('verification.member.membership_status', 'suspended')
                ->where('verification.member.is_active', false)
                ->where('verification.member.is_inactive', true)
                ->where('verification.member.membership_status_label', 'Digantung')
            );
    }

    public function test_missing_profile_photo_disables_download_and_share_readiness(): void
    {
        $this->member->update([
            'profile_photo_path' => null,
        ]);

        $this->actingAs($this->memberUser)
            ->get('/member/card')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('card.readiness.has_profile_photo', false)
                ->where('card.readiness.is_ready', false)
                ->where('card.readiness.notice', 'Muat naik gambar profil untuk mengaktifkan Kad Keahlian Digital anda.')
            );

        $this->actingAs($this->memberUser)
            ->get('/member/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('digitalCard.readiness.has_profile_photo', false)
                ->where('digitalCard.readiness.is_ready', false)
            );
    }
}
