<?php

namespace Tests\Feature\Financing;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FinancingCategoryType;
use App\Enums\FinancingGuarantorStatus;
use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingCategory;
use App\Models\FinancingDocument;
use App\Models\FinancingGuarantor;
use App\Models\FinancingProduct;
use App\Models\Member;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\FinancingWorkflowNotification;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected Unit $loanUnit;

    protected User $admin;

    protected User $memberUser;

    protected Member $member;

    protected FinancingCategory $guaranteedCategory;

    protected FinancingCategory $nonGuaranteedCategory;

    protected FinancingProduct $guaranteedProduct;

    protected FinancingProduct $nonGuaranteedProduct;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create(['status' => 'active']);

        $superAdmin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_SUPER_ADMIN,
        ]);
        $superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);

        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
            'staff_id' => 'ADM001',
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);

        $this->loanUnit = Unit::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Pinjaman',
            'slug' => 'unit-pinjaman',
            'description' => 'Unit pembiayaan demo',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $superAdmin->id,
            'updated_by' => $superAdmin->id,
        ]);

        $this->admin->update(['unit_id' => $this->loanUnit->id]);

        $this->memberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $this->memberUser->assignRole(AccessControl::ROLE_MEMBER);

        $this->member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $this->memberUser->id,
            'membership_status' => MemberStatus::Active->value,
            'member_no' => 'MBR-20260505-0001',
            'full_name' => 'Ahli Demo Aktif',
        ]);

        $this->guaranteedCategory = FinancingCategory::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Pembiayaan Berpenjamin',
            'slug' => 'pembiayaan-berpenjamin',
            'type' => FinancingCategoryType::Guaranteed->value,
        ]);

        $this->nonGuaranteedCategory = FinancingCategory::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Pembiayaan Tanpa Penjamin',
            'slug' => 'pembiayaan-tanpa-penjamin',
            'type' => FinancingCategoryType::NonGuaranteed->value,
        ]);

        $this->guaranteedProduct = FinancingProduct::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_category_id' => $this->guaranteedCategory->id,
            'unit_id' => $this->loanUnit->id,
            'name' => 'Pembiayaan Peribadi Berpenjamin',
            'slug' => 'pembiayaan-peribadi-berpenjamin',
            'requires_guarantor' => true,
            'guarantor_count' => 2,
            'required_documents_json' => ['Slip gaji terkini'],
        ]);

        $this->nonGuaranteedProduct = FinancingProduct::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'unit_id' => $this->loanUnit->id,
            'name' => 'Pembiayaan Kecil Tanpa Penjamin',
            'slug' => 'pembiayaan-kecil-tanpa-penjamin',
            'requires_guarantor' => false,
            'guarantor_count' => 0,
            'required_documents_json' => ['Salinan kad pengenalan'],
        ]);
    }

    public function test_admin_can_manage_financing_categories_and_upload_rate_image(): void
    {
        $file = UploadedFile::fake()->image('kadar.png', 1200, 900);

        $this->actingAs($this->admin)
            ->post('/admin/financing/categories', [
                'name' => 'Kategori Ujian',
                'slug' => 'kategori-ujian',
                'description' => 'Penerangan demo',
                'type' => 'guaranteed',
                'rate_image' => $file,
                'is_active' => true,
                'sort_order' => 10,
            ])
            ->assertRedirect();

        $category = FinancingCategory::query()->where('slug', 'kategori-ujian')->firstOrFail();

        $this->assertNotNull($category->rate_image_path);
        Storage::disk('public')->assertExists($category->rate_image_path);
    }

    public function test_admin_can_manage_financing_products_and_member_can_view_active_products(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Pembiayaan Barangan Tanpa Penjamin',
                'slug' => 'pembiayaan-barangan-tanpa-penjamin',
                'description' => 'Produk demo',
                'min_amount' => 1000,
                'max_amount' => 7000,
                'min_tenure_months' => 6,
                'max_tenure_months' => 24,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'required_documents_text' => "Sebutharga barangan\nSalinan kad pengenalan",
                'is_active' => true,
                'sort_order' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('financing_products', [
            'slug' => 'pembiayaan-barangan-tanpa-penjamin',
        ]);

        $this->actingAs($this->memberUser)
            ->get('/member/financing')
            ->assertOk()
            ->assertSee('Pembiayaan Barangan Tanpa Penjamin');
    }

    public function test_member_can_submit_non_guaranteed_application_and_notifications_are_dispatched(): void
    {
        $file = UploadedFile::fake()->create('ic.pdf', 100, 'application/pdf');

        $this->actingAs($this->memberUser)
            ->post('/member/financing/applications', [
                'financing_product_id' => $this->nonGuaranteedProduct->id,
                'amount_requested' => 3500,
                'tenure_months' => 12,
                'purpose' => 'Keperluan kecemasan keluarga',
                'monthly_income' => 3200,
                'monthly_commitment' => 500,
                'employment_notes' => 'Bekerja tetap',
                'documents' => [$file],
            ])
            ->assertRedirect();

        $application = FinancingApplication::query()->where('member_id', $this->member->id)->latest('id')->firstOrFail();

        $this->assertSame(FinancingApplicationStatus::Submitted, $application->status);
        $this->assertDatabaseHas('financing_documents', [
            'financing_application_id' => $application->id,
            'label' => 'Salinan kad pengenalan',
        ]);
        Notification::assertSentTo($this->memberUser, FinancingWorkflowNotification::class);
        Notification::assertSentTo($this->admin, FinancingWorkflowNotification::class);
    }

    public function test_guarantor_search_only_returns_active_members_with_login_and_excludes_self(): void
    {
        $eligibleUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
            'staff_id' => 'STF100',
        ]);
        $eligibleUser->assignRole(AccessControl::ROLE_MEMBER);

        $eligibleMember = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $eligibleUser->id,
            'membership_status' => MemberStatus::Active->value,
            'full_name' => 'Penjamin Layak',
            'member_no' => 'MBR-20260505-0100',
        ]);

        Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => null,
            'membership_status' => MemberStatus::Active->value,
            'full_name' => 'Tiada Login',
        ]);

        $response = $this->actingAs($this->memberUser)
            ->getJson('/member/financing/guarantor-search?search=Penjamin');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $eligibleMember->id,
                'name' => 'Penjamin Layak',
            ])
            ->assertJsonMissing([
                'id' => $this->member->id,
            ]);
    }

    public function test_member_cannot_select_self_or_duplicate_guarantor(): void
    {
        $guarantorUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $guarantorUser->assignRole(AccessControl::ROLE_MEMBER);

        $guarantorMember = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $guarantorUser->id,
            'membership_status' => MemberStatus::Active->value,
        ]);

        $this->actingAs($this->memberUser)
            ->from('/member/financing/applications/create?product='.$this->guaranteedProduct->id)
            ->post('/member/financing/applications', [
                'financing_product_id' => $this->guaranteedProduct->id,
                'amount_requested' => 10000,
                'tenure_months' => 24,
                'purpose' => 'Ujian penjamin',
                'guarantor_member_ids' => [$this->member->id, $guarantorMember->id],
            ])
            ->assertSessionHasErrors('guarantor_member_ids');

        $this->actingAs($this->memberUser)
            ->from('/member/financing/applications/create?product='.$this->guaranteedProduct->id)
            ->post('/member/financing/applications', [
                'financing_product_id' => $this->guaranteedProduct->id,
                'amount_requested' => 10000,
                'tenure_months' => 24,
                'purpose' => 'Ujian penjamin',
                'guarantor_member_ids' => [$guarantorMember->id, $guarantorMember->id],
            ])
            ->assertSessionHasErrors('guarantor_member_ids');
    }

    public function test_member_can_submit_guaranteed_application_and_guarantor_access_is_protected(): void
    {
        [$guarantorOneUser, $guarantorOneMember] = $this->createGuarantor('STF201', 'Penjamin Satu');
        [$guarantorTwoUser, $guarantorTwoMember] = $this->createGuarantor('STF202', 'Penjamin Dua');

        $this->actingAs($this->memberUser)
            ->post('/member/financing/applications', [
                'financing_product_id' => $this->guaranteedProduct->id,
                'amount_requested' => 12000,
                'tenure_months' => 24,
                'purpose' => 'Kecemasan keluarga',
                'guarantor_member_ids' => [$guarantorOneMember->id, $guarantorTwoMember->id],
            ])
            ->assertRedirect();

        $application = FinancingApplication::query()->where('financing_product_id', $this->guaranteedProduct->id)->latest('id')->firstOrFail();
        $guarantorRequest = FinancingGuarantor::query()
            ->where('financing_application_id', $application->id)
            ->where('guarantor_member_id', $guarantorOneMember->id)
            ->firstOrFail();

        $this->assertSame(FinancingApplicationStatus::GuarantorPending, $application->status);
        Notification::assertSentTo($guarantorOneUser, FinancingWorkflowNotification::class);
        Notification::assertSentTo($guarantorTwoUser, FinancingWorkflowNotification::class);

        $this->actingAs($guarantorOneUser)
            ->get("/member/financing/guarantor-requests/{$guarantorRequest->id}")
            ->assertOk();

        $this->actingAs($this->memberUser)
            ->get("/member/financing/guarantor-requests/{$guarantorRequest->id}")
            ->assertNotFound();
    }

    public function test_guarantor_must_provide_consent_and_signature_to_accept_and_can_reject_with_reason(): void
    {
        [$guarantorUser, $guarantorMember] = $this->createGuarantor('STF301', 'Penjamin Tiga');
        [$otherGuarantorUser, $otherGuarantorMember] = $this->createGuarantor('STF302', 'Penjamin Empat');

        $application = $this->createGuaranteedApplication($guarantorMember, $otherGuarantorMember);
        $guarantorRequest = FinancingGuarantor::query()
            ->where('financing_application_id', $application->id)
            ->where('guarantor_member_id', $guarantorMember->id)
            ->firstOrFail();

        $this->actingAs($guarantorUser)
            ->from("/member/financing/guarantor-requests/{$guarantorRequest->id}")
            ->post("/member/financing/guarantor-requests/{$guarantorRequest->id}", [
                'action' => 'accept',
                'consent' => false,
                'signature' => '',
            ])
            ->assertSessionHasErrors(['consent', 'signature']);

        $this->actingAs($guarantorUser)
            ->post("/member/financing/guarantor-requests/{$guarantorRequest->id}", [
                'action' => 'reject',
                'rejection_reason' => 'Tidak layak menjadi penjamin.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $application->refresh();
        $guarantorRequest->refresh();

        $this->assertSame(FinancingGuarantorStatus::Rejected, $guarantorRequest->status);
        $this->assertSame(FinancingApplicationStatus::GuarantorRejected, $application->status);
    }

    public function test_application_status_changes_after_all_guarantors_accept_and_admin_can_approve_with_history_visible(): void
    {
        [$guarantorOneUser, $guarantorOneMember] = $this->createGuarantor('STF401', 'Penjamin Lima');
        [$guarantorTwoUser, $guarantorTwoMember] = $this->createGuarantor('STF402', 'Penjamin Enam');

        $priorApplication = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::Approved->value,
            'approved_amount' => 3000,
            'approved_tenure_months' => 12,
        ]);

        $application = $this->createGuaranteedApplication($guarantorOneMember, $guarantorTwoMember);

        foreach ([$guarantorOneUser, $guarantorTwoUser] as $user) {
            $requestRecord = FinancingGuarantor::query()
                ->where('financing_application_id', $application->id)
                ->whereHas('guarantorMember', fn ($query) => $query->where('user_id', $user->id))
                ->firstOrFail();

            $this->actingAs($user)
                ->post("/member/financing/guarantor-requests/{$requestRecord->id}", [
                    'action' => 'accept',
                    'consent' => true,
                    'signature' => 'data:image/png;base64,'.base64_encode('signature-binary'),
                ])
                ->assertRedirect();
        }

        $application->refresh();
        $this->assertSame(FinancingApplicationStatus::GuarantorAccepted, $application->status);

        $this->actingAs($this->admin)
            ->post("/admin/financing/applications/{$application->id}/under-review", [
                'decision_notes' => 'Semakan awal selesai.',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post("/admin/financing/applications/{$application->id}/approve", [
                'approved_amount' => 9500,
                'approved_tenure_months' => 18,
                'decision_notes' => 'Diluluskan untuk demo.',
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(FinancingApplicationStatus::Approved, $application->status);

        $this->actingAs($this->admin)
            ->get("/admin/financing/applications/{$application->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Financing/Applications/Show', false)
                ->where('application.reference_no', $application->reference_no)
                ->has('application.applicant_history', 2)
            );
    }

    public function test_sensitive_financing_documents_are_protected_and_member_cannot_access_admin_financing_pages(): void
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::Submitted->value,
        ]);

        Storage::disk('local')->put('financing/documents/rahsia.pdf', 'rahsia');

        $document = FinancingDocument::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_application_id' => $application->id,
            'uploaded_by' => $this->memberUser->id,
            'file_path' => 'financing/documents/rahsia.pdf',
            'file_name' => 'rahsia.pdf',
        ]);

        $otherMemberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $otherMemberUser->assignRole(AccessControl::ROLE_MEMBER);

        $otherMember = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $otherMemberUser->id,
            'membership_status' => MemberStatus::Active->value,
        ]);

        $this->actingAs($otherMemberUser)
            ->get("/member/financing/applications/{$application->id}")
            ->assertNotFound();

        $this->actingAs($otherMemberUser)
            ->get("/member/financing/applications/{$application->id}/documents/{$document->id}/download")
            ->assertNotFound();

        $this->actingAs($this->memberUser)
            ->get('/admin/financing/applications')
            ->assertRedirect('/member/dashboard');
    }

    private function createGuarantor(string $staffId, string $name): array
    {
        $user = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
            'staff_id' => $staffId,
        ]);
        $user->assignRole(AccessControl::ROLE_MEMBER);

        $member = Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $user->id,
            'membership_status' => MemberStatus::Active->value,
            'full_name' => $name,
        ]);

        return [$user, $member];
    }

    private function createGuaranteedApplication(Member $guarantorOne, Member $guarantorTwo): FinancingApplication
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->guaranteedCategory->id,
            'financing_product_id' => $this->guaranteedProduct->id,
            'status' => FinancingApplicationStatus::GuarantorPending->value,
            'reference_no' => 'FIN-20260505-0101',
        ]);

        FinancingGuarantor::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_application_id' => $application->id,
            'guarantor_member_id' => $guarantorOne->id,
            'status' => FinancingGuarantorStatus::Pending->value,
        ]);

        FinancingGuarantor::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_application_id' => $application->id,
            'guarantor_member_id' => $guarantorTwo->id,
            'status' => FinancingGuarantorStatus::Pending->value,
        ]);

        return $application;
    }
}
