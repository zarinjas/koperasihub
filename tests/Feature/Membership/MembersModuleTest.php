<?php

namespace Tests\Feature\Membership;

use App\Enums\MemberStatus;
use App\Enums\MembershipApplicationStatus;
use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MembersModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create([
            'status' => 'active',
        ]);

        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => AccessControl::ROLE_ADMIN,
            'role' => AccessControl::ROLE_ADMIN,
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);
    }

    public function test_admin_can_create_view_and_update_member_profile(): void
    {
        $memberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => AccessControl::ROLE_MEMBER,
            'role' => AccessControl::ROLE_MEMBER,
        ]);
        $memberUser->assignRole(AccessControl::ROLE_MEMBER);

        $this->actingAs($this->admin)
            ->post('/admin/members', [
                'user_id' => $memberUser->id,
                'full_name' => 'Siti Nurhaliza Binti Osman',
                'identity_no' => '920101105432',
                'email' => 'siti.member@example.test',
                'phone' => '0123456789',
                'address' => "No. 8, Jalan Damai\n43000 Kajang\nSelangor",
                'date_of_birth' => '1992-01-01',
                'gender' => 'female',
                'occupation' => 'Pegawai Pentadbiran',
                'employer_name' => 'Demo Holdings',
                'membership_status' => MemberStatus::Active->value,
                'joined_at' => '2026-05-01',
                'notes' => 'Dicipta secara manual oleh admin.',
            ])
            ->assertRedirect();

        $member = Member::query()->firstOrFail();

        $this->assertSame($memberUser->id, $member->user_id);
        $this->assertSame('Siti Nurhaliza Binti Osman', $member->full_name);
        $this->assertStringStartsWith('MBR-', $member->member_no);

        Document::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $member->id,
            'uploaded_by' => $this->admin->id,
        ]);

        MembershipApplication::factory()->approved()->create([
            'cooperative_id' => $this->cooperative->id,
            'approved_member_id' => $member->id,
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/members/{$member->id}")
            ->assertOk()
            ->assertSee($member->member_no)
            ->assertSee('Siti Nurhaliza Binti Osman');

        $replacementUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => AccessControl::ROLE_MEMBER,
            'role' => AccessControl::ROLE_MEMBER,
        ]);
        $replacementUser->assignRole(AccessControl::ROLE_MEMBER);

        $this->actingAs($this->admin)
            ->patch("/admin/members/{$member->id}", [
                'user_id' => $replacementUser->id,
                'full_name' => 'Siti Nurhaliza Binti Ahmad',
                'identity_no' => '920101105432',
                'email' => 'siti.ahmad@example.test',
                'phone' => '0198887766',
                'address' => "No. 18, Jalan Wawasan\n43000 Kajang\nSelangor",
                'date_of_birth' => '1992-01-01',
                'gender' => 'female',
                'occupation' => 'Eksekutif Operasi',
                'employer_name' => 'Demo Ventures',
                'membership_status' => MemberStatus::Active->value,
                'joined_at' => '2026-05-01',
                'notes' => 'Profil telah dikemas kini.',
            ])
            ->assertRedirect("/admin/members/{$member->id}");

        $member->refresh();

        $this->assertSame($replacementUser->id, $member->user_id);
        $this->assertSame('Siti Nurhaliza Binti Ahmad', $member->full_name);
        $this->assertSame('siti.ahmad@example.test', $member->email);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_created',
            'subject_id' => $member->id,
            'subject_type' => Member::class,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_updated',
            'subject_id' => $member->id,
            'subject_type' => Member::class,
        ]);
        $this->assertGreaterThanOrEqual(2, AuditLog::query()->where('action', 'member_linked_to_user')->count());
    }

    public function test_admin_can_change_member_status_and_filter_members(): void
    {
        $member = Member::factory()->active()->create([
            'cooperative_id' => $this->cooperative->id,
        ]);

        Member::factory()->inactive()->create([
            'cooperative_id' => $this->cooperative->id,
            'full_name' => 'Ahli Tidak Aktif',
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/members/{$member->id}/status", [
                'membership_status' => MemberStatus::Suspended->value,
            ])
            ->assertRedirect();

        $member->refresh();

        $this->assertSame(MemberStatus::Suspended, $member->membership_status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_status_changed',
            'subject_id' => $member->id,
            'subject_type' => Member::class,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/members?status=suspended')
            ->assertOk()
            ->assertSee($member->full_name)
            ->assertDontSee('Ahli Tidak Aktif');
    }

    public function test_admin_member_detail_shows_profile_photo_when_available(): void
    {
        $path = \Illuminate\Http\UploadedFile::fake()->image('admin-view.png', 540, 540)->store('member-photos', 'public');

        $member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'profile_photo_path' => $path,
            'full_name' => 'Farid Hakim',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/members/{$member->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Members/Show', false)
                ->where('member.full_name', 'Farid Hakim')
                ->where('member.profile_photo_url', Storage::disk('public')->url($path))
            );
    }

    public function test_member_routes_are_protected_when_user_lacks_member_permissions(): void
    {
        $unauthorizedAdmin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => AccessControl::ROLE_ADMIN,
            'role' => AccessControl::ROLE_ADMIN,
        ]);

        $member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
        ]);

        $this->actingAs($unauthorizedAdmin)
            ->get('/admin/members')
            ->assertForbidden();

        $this->actingAs($unauthorizedAdmin)
            ->get("/admin/members/{$member->id}")
            ->assertForbidden();

        $this->actingAs($unauthorizedAdmin)
            ->post("/admin/members/{$member->id}/status", [
                'membership_status' => MemberStatus::Inactive->value,
            ])
            ->assertForbidden();
    }

    public function test_duplicate_member_creation_is_prevented_by_identity_no_or_email(): void
    {
        Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'identity_no' => '900101105432',
            'email' => 'duplicate@example.test',
        ]);

        $this->actingAs($this->admin)
            ->from('/admin/members/create')
            ->post('/admin/members', [
                'full_name' => 'Rekod Bertindih',
                'identity_no' => '900101105432',
                'email' => 'duplicate@example.test',
                'membership_status' => MemberStatus::Active->value,
            ])
            ->assertRedirect('/admin/members/create')
            ->assertSessionHasErrors(['identity_no', 'email']);

        $this->assertDatabaseCount('members', 1);
    }

    public function test_approving_application_creates_new_member_record_and_links_application(): void
    {
        $application = MembershipApplication::factory()->underReview()->create([
            'cooperative_id' => $this->cooperative->id,
            'identity_no' => '881212105432',
            'email' => 'approve-new@example.test',
            'reviewed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/membership-applications/{$application->id}/approve")
            ->assertRedirect();

        $application->refresh();
        $member = Member::query()->findOrFail($application->approved_member_id);

        $this->assertSame(MembershipApplicationStatus::Approved, $application->status);
        $this->assertSame('approve-new@example.test', $member->email);
        $this->assertSame(MemberStatus::Active, $member->membership_status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_created',
            'subject_id' => $member->id,
            'subject_type' => Member::class,
        ]);
    }

    public function test_approving_application_links_existing_member_without_creating_duplicate(): void
    {
        $member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'identity_no' => '750101105432',
            'email' => 'existing-member@example.test',
            'joined_at' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $application = MembershipApplication::factory()->underReview()->create([
            'cooperative_id' => $this->cooperative->id,
            'identity_no' => '750101105432',
            'email' => 'existing-member@example.test',
            'reviewed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/membership-applications/{$application->id}/approve")
            ->assertRedirect();

        $application->refresh();
        $member->refresh();

        $this->assertSame($member->id, $application->approved_member_id);
        $this->assertDatabaseCount('members', 1);
        $this->assertNotNull($member->joined_at);
        $this->assertNotNull($member->approved_at);
    }
}
