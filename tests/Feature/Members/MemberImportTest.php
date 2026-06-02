<?php

namespace Tests\Feature\Members;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MemberImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Cooperative $cooperative;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create(['slug' => 'demo-test-import']);
        $this->admin = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => 'admin',
            'user_type' => 'admin',
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);
    }

    private function memberRow(
        string $memberNo,
        string $name,
        string $ic,
        string $email = '',
        string $phone = '',
        string $dob = '',
        string $gender = '',
        string $address = '',
        string $city = '',
        string $state = '',
        string $postcode = '',
        string $occupation = '',
        string $employerName = '',
        string $position = '',
        string $department = '',
        string $employer = '',
        string $status = 'active',
        string $joinedAt = '2020-05-01',
    ): array {
        return [$memberNo, $name, $ic, $email, $phone, $dob, $gender, $address, $city, $state, $postcode, $occupation, $employerName, $position, $department, $employer, $status, $joinedAt];
    }

    public function test_admin_can_download_sample_csv_template(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.members.import.template'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('member_no', $content);
        $this->assertStringContainsString('full_name', $content);
        $this->assertStringContainsString('identity_no', $content);
    }

    public function test_admin_can_import_valid_members(): void
    {
        $csvContent = $this->csvLines([
            $this->memberRow('MBR-001', 'Ali bin Abu', '900101-14-1234', 'ali@example.com', '0123456789', '1990-01-01', 'male', 'No. 1, Jalan Bukit', 'Bangi', 'Selangor', '43650', 'Pegawai', 'Demo Holdings', 'Pegawai', 'Pentadbiran', 'UKM'),
            $this->memberRow('MBR-002', 'Siti binti Bakar', '900202-14-5678', 'siti@example.com', '0123456788', '1990-02-02', 'female', 'No. 2, Jalan Damai', 'Kajang', 'Selangor', '43000', 'Guru', 'SK Damai', 'Guru', 'Pendidikan', 'Sekolah'),
        ]);

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.members.import.preview'), ['file' => $file]);

        $previewResponse->assertRedirect();
        $this->assertNotNull(session('import_preview'));

        $importResponse = $this->actingAs($this->admin)
            ->post(route('admin.members.import.store'));

        $importResponse->assertRedirect(route('admin.members.index'));
        $importResponse->assertSessionHas('status');

        $this->assertDatabaseHas('members', [
            'cooperative_id' => $this->cooperative->id,
            'member_no' => 'MBR-001',
            'full_name' => 'Ali bin Abu',
            'identity_no' => '900101-14-1234',
            'membership_status' => 'active',
        ]);

        $this->assertDatabaseHas('members', [
            'cooperative_id' => $this->cooperative->id,
            'member_no' => 'MBR-002',
            'full_name' => 'Siti binti Bakar',
            'membership_status' => 'active',
        ]);
    }

    public function test_duplicate_member_no_is_skipped(): void
    {
        Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_no' => 'MBR-001',
            'identity_no' => '900101-14-1234',
            'full_name' => 'Existing Member',
            'membership_status' => MemberStatus::Active->value,
        ]);

        $csvContent = $this->csvLines([
            $this->memberRow('MBR-001', 'Ali bin Abu', '900101-14-1234', 'ali@example.com', '0123456789', '1990-01-01', 'male', 'Bangi', 'Selangor', '43650', 'Pegawai', 'Demo Holdings', 'Pegawai', 'Pentadbiran', 'UKM'),
        ]);

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('admin.members.import.preview'), ['file' => $file]);
        $this->actingAs($this->admin)
            ->post(route('admin.members.import.store'));

        $this->assertEquals(1, Member::query()
            ->where('cooperative_id', $this->cooperative->id)
            ->where('member_no', 'MBR-001')
            ->count());
    }

    public function test_duplicate_identity_no_is_skipped(): void
    {
        Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_no' => 'MBR-EXISTING',
            'identity_no' => '900101-14-9999',
            'full_name' => 'Existing',
            'membership_status' => MemberStatus::Active->value,
        ]);

        $csvContent = $this->csvLines([
            $this->memberRow('MBR-001', 'Ali bin Abu', '900101-14-9999', 'ali@example.com', '0123456789', '1990-01-01', 'male', 'Bangi', 'Selangor', '43650', 'Pegawai', 'Demo Holdings', 'Pegawai', 'Pentadbiran', 'UKM'),
        ]);

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('admin.members.import.preview'), ['file' => $file]);
        $this->actingAs($this->admin)
            ->post(route('admin.members.import.store'));

        $this->assertEquals(0, Member::query()
            ->where('cooperative_id', $this->cooperative->id)
            ->where('member_no', 'MBR-001')
            ->count());
    }

    public function test_duplicate_email_with_existing_user_flags_error(): void
    {
        User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'email' => 'ali@example.com',
            'role' => 'member',
            'user_type' => 'member',
        ]);

        $csvContent = $this->csvLines([
            $this->memberRow('MBR-001', 'Ali bin Abu', '900101-14-1234', 'ali@example.com', '0123456789', '1990-01-01', 'male', 'Bangi', 'Selangor', '43650', 'Pegawai', 'Demo Holdings', 'Pegawai', 'Pentadbiran', 'UKM'),
        ]);

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('admin.members.import.preview'), ['file' => $file]);
        $this->actingAs($this->admin)
            ->post(route('admin.members.import.store'));

        $this->assertEquals(0, Member::query()
            ->where('cooperative_id', $this->cooperative->id)
            ->where('member_no', 'MBR-001')
            ->count());
    }

    public function test_invalid_rows_show_errors(): void
    {
        $csvContent = $this->csvLines([
            array_fill(0, 18, ''),
            $this->memberRow('MBR-OK', 'Nama OK', '900101-14-1234'),
        ]);

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.members.import.preview'), ['file' => $file]);

        $response->assertRedirect();
        $preview = session('import_preview');
        $this->assertNotNull($preview);
        $this->assertEquals(2, $preview['totalRows']);
        $this->assertGreaterThan(0, $preview['invalidRows']);
    }

    public function test_imported_member_is_pending_activation(): void
    {
        $csvContent = $this->csvLines([
            $this->memberRow('MBR-001', 'Ali bin Abu', '900101-14-1234', 'ali@example.com', '0123456789', '1990-01-01', 'male', 'Bangi', 'Selangor', '43650', 'Pegawai', 'Demo Holdings', 'Pegawai', 'Pentadbiran', 'UKM'),
        ]);

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('admin.members.import.preview'), ['file' => $file]);
        $this->actingAs($this->admin)
            ->post(route('admin.members.import.store'));

        $member = Member::query()
            ->where('cooperative_id', $this->cooperative->id)
            ->where('member_no', 'MBR-001')
            ->first();

        $this->assertNotNull($member);
        $this->assertNull($member->user_id);
        $this->assertNull($member->portal_activated_at);
    }

    private function csvLines(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn ($val) => '"' . str_replace('"', '""', $val ?? '') . '"', $row));
        }

        return implode("\n", $lines);
    }
}