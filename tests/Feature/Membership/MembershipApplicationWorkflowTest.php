<?php

namespace Tests\Feature\Membership;

use App\Enums\MembershipApplicationStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MembershipApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create([
            'status' => 'active',
        ]);

        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);
    }

    public function test_public_visitor_can_submit_membership_application(): void
    {
        $this->post('/membership/apply', [
            'full_name' => 'Siti Aminah Binti Salleh',
            'identity_no' => '900101105432',
            'email' => 'aminah@example.test',
            'phone' => '0123456789',
            'address_line_1' => "No. 1, Jalan Demo\n43000 Kajang\nSelangor",
            'date_of_birth' => '1990-01-01',
            'gender' => 'female',
            'occupation' => 'Eksekutif Operasi',
            'employer_name' => 'Demo Holdings',
            'notes' => 'Ingin menyertai keahlian untuk kemudahan simpanan.',
        ])->assertRedirect();

        $application = MembershipApplication::query()->first();

        $this->assertNotNull($application);
        $this->assertSame(MembershipApplicationStatus::Pending, $application->status);
        $this->assertStringStartsWith('APP-', $application->application_no);
        $this->assertSame('Siti Aminah Binti Salleh', $application->full_name);
    }

    public function test_public_submission_fails_closed_when_no_active_cooperative_exists(): void
    {
        $this->cooperative->update(['status' => 'inactive']);

            $this->from('/membership/apply')
            ->post('/membership/apply', [
                'full_name' => 'Siti Aminah Binti Salleh',
                'identity_no' => '900101105432',
                'email' => 'aminah@example.test',
                'phone' => '0123456789',
                'address_line_1' => "No. 1, Jalan Demo\n43000 Kajang\nSelangor",
                'date_of_birth' => '1990-01-01',
                'gender' => 'female',
            ])
            ->assertRedirect('/membership/apply')
            ->assertSessionHasErrors('cooperative');

        $this->assertDatabaseCount('membership_applications', 0);
    }

    public function test_public_submission_validation_rejects_incomplete_payload(): void
    {
            $this->from('/membership/apply')
            ->post('/membership/apply', [
                'full_name' => '',
                'identity_no' => '',
                'email' => 'tidak-sah',
            ])
            ->assertRedirect('/membership/apply')
            ->assertSessionHasErrors([
                'full_name',
                'identity_no',
                'email',
                'phone',
                'address_line_1',
                'date_of_birth',
                'gender',
            ]);
    }

    public function test_admin_routes_are_protected_when_user_lacks_membership_application_permissions(): void
    {
        $unauthorizedAdmin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);

        $application = MembershipApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
        ]);

        $this->actingAs($unauthorizedAdmin)
            ->get('/admin/membership-applications')
            ->assertForbidden();

        $this->actingAs($unauthorizedAdmin)
            ->post("/admin/membership-applications/{$application->id}/approve")
            ->assertForbidden();
    }

    public function test_admin_can_mark_application_under_review_and_reject_with_reason(): void
    {
        $application = MembershipApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'status' => MembershipApplicationStatus::Pending->value,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/membership-applications/{$application->id}/under-review", [
                'review_notes' => 'Dokumen sedang disemak oleh pegawai.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(MembershipApplicationStatus::UnderReview, $application->status);
        $this->assertSame('Dokumen sedang disemak oleh pegawai.', $application->review_notes);
        $this->assertSame($this->admin->id, $application->reviewed_by);

        $this->actingAs($this->admin)
            ->post("/admin/membership-applications/{$application->id}/reject", [
                'rejection_reason' => 'Maklumat pengenalan tidak lengkap.',
                'review_notes' => 'Sila kemas kini salinan dokumen dan mohon semula.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(MembershipApplicationStatus::Rejected, $application->status);
        $this->assertSame('Maklumat pengenalan tidak lengkap.', $application->rejection_reason);
        $this->assertSame('Sila kemas kini salinan dokumen dan mohon semula.', $application->review_notes);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application_under_review',
            'subject_id' => $application->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application_rejected',
            'subject_id' => $application->id,
        ]);
    }

    public function test_admin_can_approve_application_and_create_member_record(): void
    {
        $application = MembershipApplication::factory()->underReview()->create([
            'cooperative_id' => $this->cooperative->id,
            'reviewed_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/membership-applications/{$application->id}/approve")
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(MembershipApplicationStatus::Approved, $application->status);
        $this->assertNotNull($application->approved_member_id);
        $this->assertSame($this->admin->id, $application->reviewed_by);
        $this->assertDatabaseHas('members', [
            'id' => $application->approved_member_id,
            'cooperative_id' => $this->cooperative->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'application_approved',
            'subject_id' => $application->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_created',
            'subject_type' => Member::class,
        ]);
    }

    public function test_admin_can_filter_membership_applications_by_status(): void
    {
        MembershipApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'full_name' => 'Pemohon Menunggu',
            'status' => MembershipApplicationStatus::Pending->value,
        ]);

        MembershipApplication::factory()->approved()->create([
            'cooperative_id' => $this->cooperative->id,
            'full_name' => 'Pemohon Diluluskan',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/membership-applications?status=approved')
            ->assertOk()
            ->assertSee('Pemohon Diluluskan')
            ->assertDontSee('Pemohon Menunggu');
    }

    public function test_application_number_is_not_reused_after_soft_delete(): void
    {
        $deleted = MembershipApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'application_no' => 'APP-'.now()->format('Ymd').'-AAAAAA',
        ]);
        $deleted->delete();

        $this->post('/membership/apply', [
            'full_name' => 'Siti Aminah Binti Salleh',
            'identity_no' => '900101105432',
            'email' => 'aminah@example.test',
            'phone' => '0123456789',
            'address_line_1' => "No. 1, Jalan Demo\n43000 Kajang\nSelangor",
            'date_of_birth' => '1990-01-01',
            'gender' => 'female',
        ])->assertRedirect();

        $application = MembershipApplication::query()->latest('id')->firstOrFail();

        $this->assertNotSame($deleted->application_no, $application->application_no);
        $this->assertMatchesRegularExpression('/^APP-\d{8}-[A-Z0-9]{6}$/', $application->application_no);
    }

    public function test_public_submission_is_audit_logged(): void
    {
        $this->post('/membership/apply', [
            'full_name' => 'Farah Binti Ismail',
            'identity_no' => '900101105430',
            'email' => 'farah@example.test',
            'phone' => '0131112233',
            'address_line_1' => "No. 9, Jalan Indah\n43000 Kajang\nSelangor",
            'date_of_birth' => '1990-01-01',
            'gender' => 'female',
        ])->assertRedirect();

        $application = MembershipApplication::query()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'membership_application_submitted',
            'subject_id' => $application->id,
            'subject_type' => MembershipApplication::class,
        ]);
    }
}