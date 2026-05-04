<?php

namespace Tests\Feature\Complaints;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ComplaintWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected User $memberUser;

    protected User $otherMemberUser;

    protected Member $member;

    protected Member $otherMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create([
            'status' => 'active',
        ]);

        app(SettingsService::class)->clearCache();

        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);

        $this->memberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $this->memberUser->assignRole(AccessControl::ROLE_MEMBER);

        $this->otherMemberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $this->otherMemberUser->assignRole(AccessControl::ROLE_MEMBER);

        $this->member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $this->memberUser->id,
            'full_name' => 'Ahmad Salleh',
        ]);

        $this->otherMember = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $this->otherMemberUser->id,
            'full_name' => 'Siti Rahmah',
        ]);
    }

    public function test_member_can_submit_complaint(): void
    {
        $this->actingAs($this->memberUser)
            ->post('/member/complaints', [
                'category' => 'aduan',
                'subject' => 'Portal tidak memaparkan dokumen terkini',
                'message' => 'Saya masih melihat fail lama walaupun admin telah kemas kini.',
                'priority' => ComplaintPriority::High->value,
            ])
            ->assertRedirect();

        $complaint = Complaint::query()->first();

        $this->assertNotNull($complaint);
        $this->assertSame($this->cooperative->id, $complaint->cooperative_id);
        $this->assertSame($this->member->id, $complaint->member_id);
        $this->assertSame($this->memberUser->id, $complaint->created_by);
        $this->assertSame(ComplaintStatus::Open->value, $complaint->status->value);
        $this->assertStringStartsWith('ADU-', $complaint->ticket_no);
    }

    public function test_member_can_only_access_own_complaint_data(): void
    {
        $ownComplaint = Complaint::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'created_by' => $this->memberUser->id,
            'subject' => 'Aduan Sendiri',
        ]);

        $otherComplaint = Complaint::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->otherMember->id,
            'created_by' => $this->otherMemberUser->id,
            'subject' => 'Aduan Orang Lain',
        ]);

        $this->actingAs($this->memberUser)
            ->get('/member/complaints')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Complaints/Index', false)
                ->has('complaints', 1)
                ->where('complaints.0.subject', 'Aduan Sendiri')
            );

        $this->actingAs($this->memberUser)
            ->get("/member/complaints/{$ownComplaint->id}")
            ->assertOk();

        $this->actingAs($this->memberUser)
            ->get("/member/complaints/{$otherComplaint->id}")
            ->assertForbidden();
    }

    public function test_admin_can_reply_to_complaint_and_action_is_audit_logged(): void
    {
        $complaint = Complaint::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'created_by' => $this->memberUser->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/complaints/{$complaint->id}/replies", [
                'message' => 'Pihak admin sedang meneliti isu ini.',
                'is_internal' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('complaint_replies', [
            'complaint_id' => $complaint->id,
            'user_id' => $this->admin->id,
            'message' => 'Pihak admin sedang meneliti isu ini.',
            'is_internal' => 0,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'complaint_replied',
            'actor_id' => $this->admin->id,
            'subject_type' => Complaint::class,
            'subject_id' => $complaint->id,
        ]);
    }

    public function test_admin_can_update_status_and_close_complaint(): void
    {
        $complaint = Complaint::factory()->open()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'created_by' => $this->memberUser->id,
            'priority' => ComplaintPriority::Low->value,
        ]);

        $this->actingAs($this->admin)
            ->patch("/admin/complaints/{$complaint->id}", [
                'status' => ComplaintStatus::Closed->value,
                'priority' => ComplaintPriority::High->value,
                'assigned_to' => (string) $this->admin->id,
            ])
            ->assertRedirect();

        $complaint->refresh();

        $this->assertSame(ComplaintStatus::Closed->value, $complaint->status->value);
        $this->assertSame(ComplaintPriority::High->value, $complaint->priority->value);
        $this->assertSame($this->admin->id, $complaint->assigned_to);
        $this->assertNotNull($complaint->closed_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'complaint_status_changed',
            'actor_id' => $this->admin->id,
            'subject_type' => Complaint::class,
            'subject_id' => $complaint->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'complaint_closed',
            'actor_id' => $this->admin->id,
            'subject_type' => Complaint::class,
            'subject_id' => $complaint->id,
        ]);
    }

    public function test_internal_admin_notes_are_hidden_from_members(): void
    {
        $complaint = Complaint::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'created_by' => $this->memberUser->id,
        ]);

        $complaint->replies()->create([
            'user_id' => $this->admin->id,
            'message' => 'Balasan boleh dilihat oleh ahli.',
            'is_internal' => false,
        ]);
        $complaint->replies()->create([
            'user_id' => $this->admin->id,
            'message' => 'Nota dalaman untuk admin sahaja.',
            'is_internal' => true,
        ]);

        $this->actingAs($this->memberUser)
            ->get("/member/complaints/{$complaint->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Complaints/Show', false)
                ->has('complaint.replies', 1)
                ->where('complaint.replies.0.message', 'Balasan boleh dilihat oleh ahli.')
            )
            ->assertDontSee('Nota dalaman untuk admin sahaja.');
    }

    public function test_permission_protection_blocks_unauthorized_admin_actions(): void
    {
        $restrictedAdmin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $restrictedAdmin->givePermissionTo(AccessControl::PERMISSION_VIEW_COMPLAINTS);

        $complaint = Complaint::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'created_by' => $this->memberUser->id,
        ]);

        $this->actingAs($restrictedAdmin)
            ->post("/admin/complaints/{$complaint->id}/replies", [
                'message' => 'Tidak dibenarkan',
            ])
            ->assertForbidden();

        $this->actingAs($restrictedAdmin)
            ->patch("/admin/complaints/{$complaint->id}", [
                'status' => ComplaintStatus::Resolved->value,
                'priority' => ComplaintPriority::Medium->value,
                'assigned_to' => '',
            ])
            ->assertForbidden();

        $this->assertSame(0, AuditLog::query()->count());
    }

    public function test_member_submission_is_audit_logged(): void
    {
        $this->actingAs($this->memberUser)
            ->post('/member/complaints', [
                'category' => 'aduan',
                'subject' => 'Aduan sistem',
                'message' => 'Paparan tidak dikemas kini.',
                'priority' => ComplaintPriority::Medium->value,
            ])
            ->assertRedirect();

        $complaint = Complaint::query()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'complaint_submitted',
            'subject_id' => $complaint->id,
            'subject_type' => Complaint::class,
        ]);
    }
}
