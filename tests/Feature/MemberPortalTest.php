<?php

namespace Tests\Feature;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Enums\DocumentVisibility;
use App\Enums\MembershipApplicationStatus;
use App\Models\Announcement;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $memberUser;

    protected Member $member;

    private function memberPhotoUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create([
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
            'full_name' => 'Ahmad Fahmi Bin Salleh',
            'member_no' => 'MBR-20260503-0001',
            'phone' => '01111111111',
            'address_line_1' => 'Alamat asal',
            'occupation' => 'Kerani',
            'employer_name' => 'Syarikat Asal',
        ]);
    }

    public function test_member_dashboard_shows_own_summary_application_and_quick_actions(): void
    {
        $photoPath = UploadedFile::fake()->image('dashboard-photo.png', 540, 540)->store('member-photos', 'public');
        $this->member->forceFill(['profile_photo_path' => $photoPath])->save();

        MembershipApplication::factory()->approved()->create([
            'cooperative_id' => $this->cooperative->id,
            'approved_member_id' => $this->member->id,
            'application_no' => 'APP-202605-0001',
            'status' => MembershipApplicationStatus::Approved->value,
        ]);

        Storage::disk('local')->put('documents/portal-sendiri.pdf', 'portal');

        Document::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'uploaded_by' => $this->memberUser->id,
            'title' => 'Dokumen Peribadi Ahmad',
            'file_path' => 'documents/portal-sendiri.pdf',
            'file_name' => 'portal-sendiri.pdf',
            'visibility' => DocumentVisibility::SpecificMember->value,
        ]);

        Announcement::factory()->published()->public()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->memberUser->id,
            'updated_by' => $this->memberUser->id,
            'title' => 'Hebahan Public Portal',
        ]);

        Announcement::factory()->published()->membersOnly()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->memberUser->id,
            'updated_by' => $this->memberUser->id,
            'title' => 'Hebahan Ahli Portal',
        ]);

        $this->actingAs($this->memberUser)
            ->get('/member/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Dashboard', false)
                ->where('member.full_name', 'Ahmad Fahmi Bin Salleh')
                ->where('member.member_no', 'MBR-20260503-0001')
                ->where('member.profile_photo_url', $this->memberPhotoUrl($photoPath))
                ->where('digitalCard.profile_photo_url', $this->memberPhotoUrl($photoPath))
                ->where('auth.user.profile_photo_url', $this->memberPhotoUrl($photoPath))
                ->where('application.application_no', 'APP-202605-0001')
                ->where('quickActions.0.label', 'Kemaskini Profil')
                ->where('quickActions.1.label', 'Permohonan Borang')
                ->where('quickActions.2.label', 'Mohon Pembiayaan Baru')
                ->where('quickActions.2.href', route('member.financing.index'))
                ->has('latestAnnouncements', 2)
            );
    }

    public function test_member_can_update_selected_own_profile_fields(): void
    {
        $this->actingAs($this->memberUser)
            ->from('/member/profile')
            ->patch('/member/profile?edit=1', [
                'full_name' => 'Ahmad Fahmi Bin Ismail',
                'email' => 'ahmad.fahmi@example.test',
                'phone' => '0199988877',
                'address' => "No. 21, Jalan Aman\n43000 Kajang",
                'date_of_birth' => '1991-03-15',
                'gender' => 'male',
                'occupation' => 'Pegawai Operasi',
                'employer_name' => 'Koperasi Demo Holdings',
            ])
            ->assertRedirect('/member/profile?edit=1');

        $this->member->refresh();
        $this->memberUser->refresh();

        $this->assertSame('Ahmad Fahmi Bin Ismail', $this->member->full_name);
        $this->assertSame('ahmad.fahmi@example.test', $this->member->email);
        $this->assertSame('0199988877', $this->member->phone);
        $this->assertSame("No. 21, Jalan Aman\n43000 Kajang", $this->member->address_line_1);
        $this->assertSame('1991-03-15', $this->member->date_of_birth?->format('Y-m-d'));
        $this->assertSame('male', $this->member->gender);
        $this->assertSame('Pegawai Operasi', $this->member->occupation);
        $this->assertSame('Koperasi Demo Holdings', $this->member->employer_name);
        $this->assertSame('Ahmad Fahmi Bin Ismail', $this->memberUser->name);
        $this->assertSame('ahmad.fahmi@example.test', $this->memberUser->email);
        $this->assertSame('0199988877', $this->memberUser->phone);
    }

    public function test_member_can_upload_and_replace_own_profile_photo(): void
    {
        $oldPath = UploadedFile::fake()->image('lama.png', 540, 540)->store('member-photos', 'public');
        $this->member->forceFill(['profile_photo_path' => $oldPath])->save();

        $file = UploadedFile::fake()->image('profil-baharu.png', 540, 540)->size(512);

        $this->actingAs($this->memberUser)
            ->from('/member/profile')
            ->post('/member/profile?edit=1', [
                '_method' => 'patch',
                'full_name' => $this->member->full_name,
                'email' => $this->member->email ?? $this->memberUser->email,
                'phone' => $this->member->phone,
                'address' => $this->member->address_line_1,
                'date_of_birth' => $this->member->date_of_birth?->format('Y-m-d'),
                'gender' => $this->member->gender,
                'occupation' => $this->member->occupation,
                'employer_name' => $this->member->employer_name,
                'profile_photo' => $file,
            ])
            ->assertRedirect('/member/profile?edit=1');

        $this->member->refresh();

        $this->assertNotNull($this->member->profile_photo_path);
        $this->assertStringStartsWith('member-photos/', $this->member->profile_photo_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($this->member->profile_photo_path);
    }

    public function test_member_profile_photo_upload_removes_dashboard_card_notice(): void
    {
        $file = UploadedFile::fake()->image('profil-dashboard.png', 540, 540)->size(512);

        $this->actingAs($this->memberUser)
            ->from('/member/profile')
            ->post('/member/profile', [
                '_method' => 'patch',
                'profile_photo' => $file,
            ])
            ->assertRedirect('/member/profile');

        $this->member->refresh();

        $this->assertNotNull($this->member->profile_photo_path);

        $this->actingAs($this->memberUser)
            ->get('/member/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('digitalCard.readiness.has_profile_photo', true)
                ->where('digitalCard.readiness.is_ready', true)
                ->where('digitalCard.readiness.notice', null)
                ->where('digitalCard.profile_photo_url', $this->memberPhotoUrl($this->member->profile_photo_path))
            );
    }

    public function test_member_can_upload_profile_photo_without_resubmitting_other_profile_fields(): void
    {
        $file = UploadedFile::fake()->image('profil-sahaja.png', 540, 540)->size(512);

        $originalMemberPhone = $this->member->phone;
        $originalUserPhone = $this->memberUser->phone;
        $originalAddress = $this->member->address_line_1;
        $originalOccupation = $this->member->occupation;
        $originalEmployer = $this->member->employer_name;

        $this->actingAs($this->memberUser)
            ->from('/member/profile')
            ->post('/member/profile', [
                '_method' => 'patch',
                'profile_photo' => $file,
            ])
            ->assertRedirect('/member/profile');

        $this->member->refresh();
        $this->memberUser->refresh();

        $this->assertNotNull($this->member->profile_photo_path);
        $this->assertSame($originalMemberPhone, $this->member->phone);
        $this->assertSame($originalAddress, $this->member->address_line_1);
        $this->assertSame($originalOccupation, $this->member->occupation);
        $this->assertSame($originalEmployer, $this->member->employer_name);
        $this->assertSame($originalUserPhone, $this->memberUser->phone);
        Storage::disk('public')->assertExists($this->member->profile_photo_path);
    }

    public function test_member_profile_photo_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('profil.pdf', 100, 'application/pdf');

        $this->actingAs($this->memberUser)
            ->from('/member/profile')
            ->post('/member/profile', [
                '_method' => 'patch',
                'full_name' => $this->member->full_name,
                'email' => $this->member->email ?? $this->memberUser->email,
                'phone' => $this->member->phone,
                'address' => $this->member->address_line_1,
                'date_of_birth' => $this->member->date_of_birth?->format('Y-m-d'),
                'gender' => $this->member->gender,
                'occupation' => $this->member->occupation,
                'employer_name' => $this->member->employer_name,
                'profile_photo' => $file,
            ])
            ->assertRedirect('/member/profile')
            ->assertSessionHasErrors('profile_photo');

        $this->member->refresh();
        $this->assertNull($this->member->profile_photo_path);
    }

    public function test_member_profile_update_only_affects_own_member_record(): void
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
            'full_name' => 'Nur Hidayah Binti Rahman',
            'profile_photo_path' => null,
        ]);

        $file = UploadedFile::fake()->image('nur-hidayah.png', 540, 540)->size(400);

        $this->actingAs($otherUser)
            ->post('/member/profile?edit=1', [
                '_method' => 'patch',
                'full_name' => 'Nur Hidayah Binti Rahman',
                'email' => 'nur.hidayah@example.test',
                'phone' => '0188877665',
                'address' => 'Alamat baharu ahli kedua',
                'date_of_birth' => '1994-05-05',
                'gender' => 'female',
                'occupation' => 'Penyelia',
                'employer_name' => 'Koperasi Kedua',
                'profile_photo' => $file,
            ])
            ->assertRedirect('/member/profile?edit=1');

        $this->member->refresh();
        $otherMember->refresh();

        $this->assertNull($this->member->profile_photo_path);
        $this->assertSame('01111111111', $this->member->phone);
        $this->assertNotNull($otherMember->profile_photo_path);
        $this->assertSame('0188877665', $otherMember->phone);
        Storage::disk('public')->assertExists($otherMember->profile_photo_path);
    }

    public function test_member_profile_page_shows_kemaskini_profil_action(): void
    {
        $this->actingAs($this->memberUser)
            ->get('/member/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Profile', false)
                ->where('editing', false)
                ->where('member.member_no', 'MBR-20260503-0001')
            );
    }

    public function test_member_cannot_update_member_number_from_own_profile(): void
    {
        $originalMemberNo = $this->member->member_no;

        $this->actingAs($this->memberUser)
            ->patch('/member/profile?edit=1', [
                'full_name' => 'Ahmad Fahmi Bin Salleh',
                'email' => 'ahmad.profile@example.test',
                'phone' => '0188001122',
                'member_no' => 'MBR-OVERRIDE-9999',
            ])
            ->assertRedirect('/member/profile?edit=1');

        $this->member->refresh();

        $this->assertSame($originalMemberNo, $this->member->member_no);
        $this->assertSame('0188001122', $this->member->phone);
    }

    public function test_member_cannot_access_admin_member_edit_routes(): void
    {
        $this->actingAs($this->memberUser)
            ->get("/admin/members/{$this->member->id}/edit")
            ->assertRedirect('/member/dashboard');

        $this->actingAs($this->memberUser)
            ->patch("/admin/members/{$this->member->id}", [
                'member_no' => 'MBR-20260503-9999',
                'full_name' => 'Cubaan Tidak Sah',
                'membership_status' => 'active',
            ])
            ->assertRedirect('/member/dashboard');
    }

    public function test_member_can_only_view_and_download_their_own_or_member_visible_documents(): void
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
            'full_name' => 'Siti Aisyah',
        ]);

        Storage::disk('local')->put('documents/sendiri.pdf', 'self');
        Storage::disk('local')->put('documents/ahli.pdf', 'members');
        Storage::disk('local')->put('documents/orang-lain.pdf', 'other');

        $ownDocument = Document::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'uploaded_by' => $this->memberUser->id,
            'title' => 'Slip Ahli Ahmad',
            'file_path' => 'documents/sendiri.pdf',
            'file_name' => 'sendiri.pdf',
            'visibility' => DocumentVisibility::SpecificMember->value,
        ]);

        Document::factory()->membersOnly()->create([
            'cooperative_id' => $this->cooperative->id,
            'uploaded_by' => $this->memberUser->id,
            'title' => 'Panduan Ahli',
            'file_path' => 'documents/ahli.pdf',
            'file_name' => 'ahli.pdf',
        ]);

        $otherDocument = Document::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $otherMember->id,
            'uploaded_by' => $otherUser->id,
            'title' => 'Dokumen Sulit Siti',
            'file_path' => 'documents/orang-lain.pdf',
            'file_name' => 'orang-lain.pdf',
            'visibility' => DocumentVisibility::SpecificMember->value,
        ]);

        $this->actingAs($this->memberUser)
            ->get('/member/documents')
            ->assertOk()
            ->assertSee('Slip Ahli Ahmad')
            ->assertSee('Panduan Ahli')
            ->assertDontSee('Dokumen Sulit Siti');

        $this->actingAs($this->memberUser)
            ->get("/member/documents/{$ownDocument->id}/download")
            ->assertOk()
            ->assertDownload('sendiri.pdf');

        $this->actingAs($this->memberUser)
            ->get("/member/documents/{$otherDocument->id}/download")
            ->assertForbidden();
    }

    public function test_member_announcements_page_shows_public_and_members_only_records_only(): void
    {
        Announcement::factory()->published()->public()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->memberUser->id,
            'updated_by' => $this->memberUser->id,
            'title' => 'Hebahan Public Ahli',
        ]);

        Announcement::factory()->published()->membersOnly()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->memberUser->id,
            'updated_by' => $this->memberUser->id,
            'title' => 'Hebahan Dalaman Ahli',
        ]);

        Announcement::factory()->published()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->memberUser->id,
            'updated_by' => $this->memberUser->id,
            'title' => 'Hebahan Admin Sahaja',
            'audience' => AnnouncementAudience::Admins->value,
        ]);

        Announcement::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->memberUser->id,
            'updated_by' => $this->memberUser->id,
            'title' => 'Draf Portal',
            'audience' => AnnouncementAudience::Members->value,
            'status' => AnnouncementStatus::Draft->value,
        ]);

        Announcement::factory()->published()->membersOnly()->create([
            'cooperative_id' => $this->cooperative->id,
            'created_by' => $this->memberUser->id,
            'updated_by' => $this->memberUser->id,
            'title' => 'Pengumuman Tamat',
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->memberUser)
            ->get('/member/announcements')
            ->assertOk()
            ->assertSee('Hebahan Public Ahli')
            ->assertSee('Hebahan Dalaman Ahli')
            ->assertDontSee('Hebahan Admin Sahaja')
            ->assertDontSee('Draf Portal')
            ->assertDontSee('Pengumuman Tamat');
    }

    public function test_member_portal_routes_remain_protected_from_unauthorized_access(): void
    {
        $this->get('/member/profile')
            ->assertRedirect('/member/login');

        $admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $admin->assignRole(AccessControl::ROLE_ADMIN);

        $this->actingAs($admin)
            ->get('/member/profile')
            ->assertRedirect('/admin/dashboard');

        $restrictedMember = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $restrictedMember->assignRole(AccessControl::ROLE_MEMBER);
        $restrictedMember->syncRoles([]);

        $this->actingAs($restrictedMember)
            ->get('/member/announcements')
            ->assertForbidden();
    }
}