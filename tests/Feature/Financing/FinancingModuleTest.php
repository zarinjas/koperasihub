<?php

namespace Tests\Feature\Financing;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FinancingCategoryType;
use App\Enums\FinancingGuarantorStatus;
use App\Enums\MemberStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingCategory;
use App\Models\FinancingDocument;
use App\Models\FinancingGuarantor;
use App\Models\FinancingProduct;
use App\Models\FinancingProductField;
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
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected Unit $loanUnit;

    protected User $admin;

    protected User $superAdmin;

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

        $this->superAdmin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_SUPER_ADMIN,
        ]);
        $this->superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);

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
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
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

    public function test_admin_cannot_create_or_edit_fixed_financing_categories(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/financing/categories')
            ->assertOk()
            ->assertDontSee('Tambah Kategori')
            ->assertDontSee("/admin/financing/categories/{$this->guaranteedCategory->id}/edit", false)
            ->assertInertia(fn (Assert $page) => $page->where('canEdit', false));

        $this->actingAs($this->admin)
            ->get('/admin/financing/categories/create')
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post('/admin/financing/categories', [
                'name' => 'Kategori Ujian',
                'type' => FinancingCategoryType::Guaranteed->value,
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get("/admin/financing/categories/{$this->guaranteedCategory->id}/edit")
            ->assertForbidden();
    }

    public function test_super_admin_can_update_fixed_category_metadata_without_changing_system_reference_fields(): void
    {
        $originalSlug = $this->guaranteedCategory->slug;
        $originalType = $this->guaranteedCategory->type->value;
        $originalSortOrder = $this->guaranteedCategory->sort_order;

        $this->actingAs($this->superAdmin)
            ->patch("/admin/financing/categories/{$this->guaranteedCategory->id}", [
                'name' => 'Pembiayaan Berpenjamin Khas',
                'description' => 'Penerangan baharu untuk paparan admin dan ahli.',
                'is_active' => false,
                'slug' => 'slug-baharu-tidak-dibenarkan',
                'type' => FinancingCategoryType::NonGuaranteed->value,
                'sort_order' => 99,
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('status', 'Kategori pembiayaan berjaya dikemas kini.');

        $this->guaranteedCategory->refresh();

        $this->assertSame('Pembiayaan Berpenjamin Khas', $this->guaranteedCategory->name);
        $this->assertSame('Penerangan baharu untuk paparan admin dan ahli.', $this->guaranteedCategory->description);
        $this->assertFalse($this->guaranteedCategory->is_active);
        $this->assertSame($originalSlug, $this->guaranteedCategory->slug);
        $this->assertSame($originalType, $this->guaranteedCategory->type->value);
        $this->assertSame($originalSortOrder, $this->guaranteedCategory->sort_order);
    }

    public function test_super_admin_category_edit_form_no_longer_shows_rate_image_fields(): void
    {
        $this->actingAs($this->superAdmin)
            ->get("/admin/financing/categories/{$this->guaranteedCategory->id}/edit")
            ->assertOk()
            ->assertDontSee('Jadual Kadar Pembiayaan')
            ->assertDontSee('Buang imej sedia ada');
    }

    public function test_admin_can_manage_financing_products_and_member_can_view_active_products(): void
    {
        $consentPdf = UploadedFile::fake()->create('consent.pdf', 200, 'application/pdf');
        $guidePdf = UploadedFile::fake()->create('panduan.pdf', 200, 'application/pdf');
        $rateImage = UploadedFile::fake()->image('kadar-produk.png', 1200, 900);

        $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Pembiayaan Barangan Tanpa Penjamin',
                'slug' => 'pembiayaan-barangan-tanpa-penjamin',
                'description' => 'Produk demo',
                'eligibility_terms' => 'Ahli aktif sekurang-kurangnya 6 bulan.',
                'product_terms' => 'Terma pembiayaan demo.',
                'application_notes' => 'Nota permohonan demo.',
                'application_instructions' => 'Arahan permohonan demo.',
                'required_documents_note' => 'Sila sediakan dokumen yang lengkap.',
                'officer_contact_name' => 'Pegawai Unit Pinjaman',
                'officer_contact_phone' => '03-12345678',
                'officer_contact_email' => 'pinjaman@example.test',
                'min_amount' => 1000,
                'max_amount' => 7000,
                'min_tenure_months' => 6,
                'max_tenure_months' => 24,
                'rate_image' => $rateImage,
                'annual_rate_percent' => 6.75,
                'rate_note' => 'Kadar khas untuk produk demo ini.',
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'required_documents_text' => "Sebutharga barangan\nSalinan kad pengenalan",
                'consent_pdf' => $consentPdf,
                'guide_pdf' => $guidePdf,
                'is_active' => true,
                'sort_order' => 3,
            ])
            ->assertRedirect();

        $product = FinancingProduct::query()->where('slug', 'pembiayaan-barangan-tanpa-penjamin')->firstOrFail();

        $this->assertDatabaseHas('financing_products', [
            'slug' => 'pembiayaan-barangan-tanpa-penjamin',
            'officer_contact_name' => 'Pegawai Unit Pinjaman',
            'annual_rate_percent' => 6.75,
            'rate_note' => 'Kadar khas untuk produk demo ini.',
        ]);
        Storage::disk('local')->assertExists($product->consent_pdf_path);
        Storage::disk('local')->assertExists($product->guide_pdf_path);
        Storage::disk('public')->assertExists($product->rate_image_path);

        $this->actingAs($this->memberUser)
            ->get('/member/financing')
            ->assertOk()
            ->assertSee('Pembiayaan Barangan Tanpa Penjamin');
    }

    public function test_admin_can_delete_financing_product_with_existing_applications_and_member_history_still_works(): void
    {
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Permission::findOrCreate(AccessControl::PERMISSION_VIEW_POSTERS, 'web');

        Storage::disk('local')->put('financing/product-documents/consent-history.pdf', 'consent-history');

        $this->nonGuaranteedProduct->update([
            'consent_pdf_path' => 'financing/product-documents/consent-history.pdf',
            'consent_pdf_name' => 'consent-history.pdf',
        ]);

        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'unit_id' => $this->loanUnit->id,
            'status' => FinancingApplicationStatus::Submitted->value,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->delete("/admin/financing/products/{$this->nonGuaranteedProduct->id}")
            ->assertRedirect('/admin/financing/products')
            ->assertSessionHas('status', 'Produk pembiayaan berjaya dipadam.');

        $this->assertSoftDeleted('financing_products', [
            'id' => $this->nonGuaranteedProduct->id,
        ]);

        $application->refresh();

        $this->assertSame('Pembiayaan Kecil Tanpa Penjamin', $application->product?->name);
        $this->assertSame('consent-history.pdf', $application->product?->consent_pdf_name);

        $this->actingAs($this->memberUser)
            ->get("/member/financing/applications/{$application->id}/product-documents/consent/download")
            ->assertOk();
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

        $this->assertSame(FinancingApplicationStatus::PendingCompletedForm, $application->status);
        $this->assertDatabaseHas('financing_documents', [
            'financing_application_id' => $application->id,
            'label' => 'Salinan kad pengenalan',
        ]);
        Notification::assertSentTo($this->memberUser, FinancingWorkflowNotification::class);
    }

    public function test_invalid_product_document_type_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Tidak Sah',
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'consent_pdf' => UploadedFile::fake()->image('consent.png'),
            ])
            ->assertSessionHasErrors('consent_pdf');
    }

    public function test_member_can_view_product_terms_and_download_product_pdf(): void
    {
        Storage::disk('local')->put('financing/product-documents/consent-demo.pdf', 'consent');
        Storage::disk('public')->put('financing/rate-images/product-rate-demo.png', 'rate-image');
        Storage::disk('public')->put('financing/rate-images/category-rate-demo.png', 'category-rate-image');

        $this->nonGuaranteedCategory->update([
            'rate_image_path' => 'financing/rate-images/category-rate-demo.png',
        ]);

        $this->nonGuaranteedProduct->update([
            'eligibility_terms' => 'Syarat kelayakan demo.',
            'product_terms' => 'Terma pembiayaan demo.',
            'consent_pdf_path' => 'financing/product-documents/consent-demo.pdf',
            'consent_pdf_name' => 'consent-demo.pdf',
            'rate_image_path' => 'financing/rate-images/product-rate-demo.png',
            'annual_rate_percent' => 5.5,
            'rate_note' => 'Kadar mengikut produk.',
        ]);

        $this->actingAs($this->memberUser)
            ->get("/member/financing/products/{$this->nonGuaranteedProduct->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Financing/ProductShow', false)
                ->where('product.eligibility_terms', 'Syarat kelayakan demo.')
                ->where('product.product_terms', 'Terma pembiayaan demo.')
                ->where('product.rate_image_url', '/storage/financing/rate-images/product-rate-demo.png')
                ->where('product.annual_rate_percent', 5.5)
                ->where('product.rate_note', 'Kadar mengikut produk.')
                ->missing('product.category.rate_image_url')
            );

        $this->actingAs($this->memberUser)
            ->get("/member/financing/applications/create?product={$this->nonGuaranteedProduct->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Financing/Applications/Create', false)
                ->where('product.rate_image_url', '/storage/financing/rate-images/product-rate-demo.png')
                ->missing('product.category_rate_image_url')
            );

        $this->actingAs($this->memberUser)
            ->get("/member/financing/products/{$this->nonGuaranteedProduct->id}/documents/consent")
            ->assertOk();
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
        $this->assertSame(FinancingApplicationStatus::PendingCompletedForm, $application->status);

        $this->actingAs($this->admin)
            ->post("/admin/financing/applications/{$application->id}/under-review", [
                'decision_notes' => 'Semakan awal selesai.',
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->memberUser)
            ->post("/member/financing/applications/{$application->id}/completed-form", [
                'completed_form' => UploadedFile::fake()->create('borang-lengkap.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame(FinancingApplicationStatus::Submitted, $application->status);
        $this->assertNotNull($application->completed_form_pdf_path);

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

    public function test_member_can_upload_completed_stamped_pdf_and_file_is_protected(): void
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::PendingCompletedForm->value,
        ]);

        $this->actingAs($this->memberUser)
            ->post("/member/financing/applications/{$application->id}/completed-form", [
                'completed_form' => UploadedFile::fake()->create('borang-lengkap.pdf', 300, 'application/pdf'),
            ])
            ->assertRedirect();

        $application->refresh();

        $this->assertSame(FinancingApplicationStatus::Submitted, $application->status);
        Storage::disk('local')->assertExists($application->completed_form_pdf_path);

        $otherMemberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $otherMemberUser->assignRole(AccessControl::ROLE_MEMBER);

        Member::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $otherMemberUser->id,
            'membership_status' => MemberStatus::Active->value,
        ]);

        $this->actingAs($otherMemberUser)
            ->get("/member/financing/applications/{$application->id}/completed-form/download")
            ->assertNotFound();
    }

    public function test_member_can_cancel_own_cancellable_application_with_reason(): void
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::PendingCompletedForm->value,
        ]);

        $this->actingAs($this->memberUser)
            ->from("/member/financing/applications/{$application->id}")
            ->post("/member/financing/applications/{$application->id}/cancel", [
                'cancellation_reason' => 'Permohonan tidak diteruskan buat masa ini.',
            ])
            ->assertRedirect("/member/financing/applications/{$application->id}");

        $application->refresh();

        $this->assertSame(FinancingApplicationStatus::Cancelled, $application->status);
        $this->assertSame($this->memberUser->id, $application->cancelled_by);
        $this->assertSame('Permohonan tidak diteruskan buat masa ini.', $application->cancellation_reason);
        $this->assertNotNull($application->cancelled_at);

        Notification::assertSentTo($this->memberUser, FinancingWorkflowNotification::class);
        Notification::assertSentTo($this->admin, FinancingWorkflowNotification::class);
    }

    public function test_member_cannot_cancel_another_members_application(): void
    {
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

        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $otherMember->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::Submitted->value,
        ]);

        $this->actingAs($this->memberUser)
            ->post("/member/financing/applications/{$application->id}/cancel", [
                'cancellation_reason' => 'Tidak lagi diperlukan.',
            ])
            ->assertNotFound();
    }

    public function test_member_cannot_cancel_non_cancellable_financing_statuses(): void
    {
        foreach ([
            FinancingApplicationStatus::UnderReview,
            FinancingApplicationStatus::Approved,
            FinancingApplicationStatus::Rejected,
            FinancingApplicationStatus::Closed,
        ] as $status) {
            $application = FinancingApplication::factory()->create([
                'cooperative_id' => $this->cooperative->id,
                'unit_id' => $this->loanUnit->id,
                'member_id' => $this->member->id,
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'financing_product_id' => $this->nonGuaranteedProduct->id,
                'status' => $status->value,
            ]);

            $this->actingAs($this->memberUser)
                ->from("/member/financing/applications/{$application->id}")
                ->post("/member/financing/applications/{$application->id}/cancel", [
                    'cancellation_reason' => 'Tidak lagi diperlukan.',
                ])
                ->assertSessionHasErrors('status');
        }
    }

    public function test_admin_can_view_member_cancellation_reason(): void
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::Cancelled->value,
            'cancelled_by' => $this->memberUser->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Komitmen kewangan semasa belum mengizinkan.',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/financing/applications/{$application->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Financing/Applications/Show', false)
                ->where('application.status', FinancingApplicationStatus::Cancelled->value)
                ->where('application.status_label', 'Dibatalkan')
                ->where('application.cancellation_reason', 'Komitmen kewangan semasa belum mengizinkan.')
                ->where('application.cancelled_by_name', $this->memberUser->name)
            );
    }

    public function test_cancelled_application_cannot_be_processed_further(): void
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::Cancelled->value,
            'cancelled_by' => $this->memberUser->id,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Permohonan dibatalkan oleh ahli.',
        ]);

        $this->actingAs($this->memberUser)
            ->from("/member/financing/applications/{$application->id}")
            ->post("/member/financing/applications/{$application->id}/completed-form", [
                'completed_form' => UploadedFile::fake()->create('borang-lengkap.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasErrors('completed_form');

        $this->actingAs($this->admin)
            ->from("/admin/financing/applications/{$application->id}")
            ->post("/admin/financing/applications/{$application->id}/under-review", [
                'decision_notes' => 'Tidak sepatutnya diproses.',
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_invalid_completed_form_upload_type_is_rejected(): void
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::PendingCompletedForm->value,
        ]);

        $this->actingAs($this->memberUser)
            ->post("/member/financing/applications/{$application->id}/completed-form", [
                'completed_form' => UploadedFile::fake()->image('borang.png'),
            ])
            ->assertSessionHasErrors('completed_form');
    }

    public function test_print_preview_routes_render(): void
    {
        $application = FinancingApplication::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $this->loanUnit->id,
            'member_id' => $this->member->id,
            'financing_category_id' => $this->nonGuaranteedCategory->id,
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'status' => FinancingApplicationStatus::Submitted->value,
            'completed_form_pdf_path' => 'financing/completed-forms/demo.pdf',
            'completed_form_original_name' => 'demo.pdf',
            'completed_form_uploaded_at' => now(),
        ]);

        $this->actingAs($this->memberUser)
            ->get("/member/financing/applications/{$application->id}/print")
            ->assertOk();

        $this->actingAs($this->admin)
            ->get("/admin/financing/applications/{$application->id}/print")
            ->assertOk();
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

    // --- Product field CRUD tests ---

    public function test_admin_can_add_product_field(): void
    {
        $product = $this->nonGuaranteedProduct;

        $this->actingAs($this->admin)
            ->postJson("/admin/financing/products/{$product->id}/fields", [
                'label' => 'Nama Waris',
                'type' => 'short_text',
                'placeholder' => 'Masukkan nama penuh waris',
                'help_text' => 'Sila isi nama waris terdekat',
                'is_required' => true,
                'is_active' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('label', 'Nama Waris')
            ->assertJsonPath('type', 'short_text')
            ->assertJsonPath('is_required', true)
            ->assertJsonPath('is_active', true);

        $this->assertDatabaseHas('financing_product_fields', [
            'financing_product_id' => $product->id,
            'label' => 'Nama Waris',
        ]);
    }

    public function test_admin_can_edit_product_field(): void
    {
        $product = $this->nonGuaranteedProduct;

        $createResponse = $this->actingAs($this->admin)
            ->postJson("/admin/financing/products/{$product->id}/fields", [
                'label' => 'Soalan Asal',
                'type' => 'short_text',
                'is_required' => false,
                'is_active' => true,
            ])
            ->assertStatus(200);

        $fieldId = $createResponse->json('id');

        $this->actingAs($this->admin)
            ->patchJson("/admin/financing/products/{$product->id}/fields/{$fieldId}", [
                'label' => 'Soalan Dikemaskini',
                'type' => 'long_text',
                'placeholder' => 'Placeholder dikemaskini',
                'help_text' => 'Bantuan dikemaskini',
                'is_required' => true,
                'is_active' => true,
            ])
            ->assertStatus(200)
            ->assertJsonPath('label', 'Soalan Dikemaskini')
            ->assertJsonPath('type', 'long_text')
            ->assertJsonPath('is_required', true);

        $this->assertDatabaseHas('financing_product_fields', [
            'id' => $fieldId,
            'label' => 'Soalan Dikemaskini',
            'type' => 'long_text',
        ]);
    }

    public function test_admin_can_reorder_product_fields(): void
    {
        $product = $this->nonGuaranteedProduct;

        $fieldA = FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Soalan Pertama',
            'field_key' => 'soalan_pertama',
            'type' => 'short_text',
            'sort_order' => 0,
        ]);

        $fieldB = FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Soalan Kedua',
            'field_key' => 'soalan_kedua',
            'type' => 'short_text',
            'sort_order' => 1,
        ]);

        $fieldC = FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Soalan Ketiga',
            'field_key' => 'soalan_ketiga',
            'type' => 'short_text',
            'sort_order' => 2,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/financing/products/{$product->id}/fields/reorder", [
                'ids' => [$fieldC->id, $fieldA->id, $fieldB->id],
            ])
            ->assertStatus(200)
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('financing_product_fields', ['id' => $fieldC->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('financing_product_fields', ['id' => $fieldA->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('financing_product_fields', ['id' => $fieldB->id, 'sort_order' => 2]);
    }

    public function test_admin_can_delete_product_field(): void
    {
        $product = $this->nonGuaranteedProduct;
        $field = FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Soalan Untuk Dipadam',
            'field_key' => 'soalan_untuk_dipadam',
            'type' => 'short_text',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/admin/financing/products/{$product->id}/fields/{$field->id}")
            ->assertStatus(200)
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('financing_product_fields', ['id' => $field->id]);
    }

    public function test_member_application_shows_configured_product_fields(): void
    {
        $product = $this->nonGuaranteedProduct;
        $product->update(['application_instructions' => 'Pastikan maklumat lengkap.']);

        FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Pernah buat pinjaman?',
            'field_key' => 'pernah_buat_pinjaman',
            'type' => 'yes_no',
            'is_required' => true,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Nota Penting',
            'field_key' => 'nota_penting',
            'type' => 'note',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Jumlah tanggungan',
            'field_key' => 'jumlah_tanggungan',
            'type' => 'number',
            'is_required' => true,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->actingAs($this->memberUser)
            ->get("/member/financing/applications/create?product={$product->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Member/Pages/Financing/Applications/Create', false)
                ->has('product.product_fields', 3)
                ->where('product.product_fields.0.label', 'Pernah buat pinjaman?')
                ->where('product.product_fields.0.is_required', true)
                ->where('product.product_fields.1.label', 'Nota Penting')
                ->where('product.product_fields.1.type', 'note')
                ->where('product.product_fields.2.label', 'Jumlah tanggungan')
                ->where('product.product_fields.2.type', 'number')
            );
    }

    public function test_required_custom_field_validation_works(): void
    {
        $product = $this->nonGuaranteedProduct;

        FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Nama Waris',
            'field_key' => 'nama_waris',
            'type' => 'short_text',
            'is_required' => true,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->memberUser)
            ->post('/member/financing/applications', [
                'financing_product_id' => $product->id,
                'amount_requested' => 3000,
                'tenure_months' => 12,
                'purpose' => 'Baik pulih kenderaan',
                'custom_answers' => [],
            ])
            ->assertInvalid(['custom_answers.nama_waris']);
    }

    public function test_content_block_fields_do_not_require_answer(): void
    {
        $product = $this->nonGuaranteedProduct;

        FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Arahan Penting',
            'field_key' => 'arahan_penting',
            'type' => 'instruction_text',
            'is_required' => false,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        FinancingProductField::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'financing_product_id' => $product->id,
            'label' => 'Nama Waris',
            'field_key' => 'nama_waris',
            'type' => 'short_text',
            'is_required' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->memberUser)
            ->post('/member/financing/applications', [
                'financing_product_id' => $product->id,
                'amount_requested' => 3000,
                'tenure_months' => 12,
                'purpose' => 'Baik pulih kenderaan',
                'custom_answers' => [
                    'nama_waris' => 'Ali bin Abu',
                ],
            ])
            ->assertRedirect();

        $application = FinancingApplication::query()->latest('id')->firstOrFail();
        $answers = $application->custom_answers_json ?? [];

        $this->assertArrayNotHasKey('arahan_penting', $answers);
        $this->assertArrayHasKey('nama_waris', $answers);
        $this->assertEquals('Ali bin Abu', $answers['nama_waris']);
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

    // --- Product create/edit flow tests ---

    public function test_admin_can_create_product_with_minimum_fields_and_redirects_to_edit_with_success_message(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Minimum Ujian',
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Produk pembiayaan berjaya disimpan.');

        $product = FinancingProduct::query()->where('slug', 'produk-minimum-ujian')->firstOrFail();

        $this->assertDatabaseHas('financing_products', [
            'id' => $product->id,
            'name' => 'Produk Minimum Ujian',
            'is_active' => true,
            'requires_guarantor' => false,
            'guarantor_count' => 0,
        ]);
    }

    public function test_admin_can_edit_product_and_data_remains_visible_after_save(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/financing/products/{$this->nonGuaranteedProduct->id}", [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Dikemas Kini',
                'description' => 'Penerangan baharu selepas kemas kini.',
                'min_amount' => 500,
                'max_amount' => 10000,
                'annual_rate_percent' => 4.25,
                'is_active' => true,
            ])
            ->assertSessionHas('status', 'Produk pembiayaan berjaya dikemas kini.');

        $this->nonGuaranteedProduct->refresh();

        $this->assertSame('Produk Dikemas Kini', $this->nonGuaranteedProduct->name);
        $this->assertSame('Penerangan baharu selepas kemas kini.', $this->nonGuaranteedProduct->description);
        $this->assertSame(500.00, (float) $this->nonGuaranteedProduct->min_amount);
        $this->assertSame(4.25, (float) $this->nonGuaranteedProduct->annual_rate_percent);

        // Verify data is visible on edit page
        $this->actingAs($this->admin)
            ->get("/admin/financing/products/{$this->nonGuaranteedProduct->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Financing/Products/Form', false)
                ->where('mode', 'edit')
                ->where('product.name', 'Produk Dikemas Kini')
                ->where('product.description', 'Penerangan baharu selepas kemas kini.')
            );
    }

    public function test_pdf_upload_persists_and_metadata_is_available_in_edit_page(): void
    {
        $consentPdf = UploadedFile::fake()->create('persetujuan.pdf', 300, 'application/pdf');
        $guidePdf = UploadedFile::fake()->create('panduan-baru.pdf', 400, 'application/pdf');

        $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Dokumen PDF',
                'consent_pdf' => $consentPdf,
                'guide_pdf' => $guidePdf,
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Produk pembiayaan berjaya disimpan.');

        $product = FinancingProduct::query()->where('slug', 'produk-dokumen-pdf')->firstOrFail();

        Storage::disk('local')->assertExists($product->consent_pdf_path);
        Storage::disk('local')->assertExists($product->guide_pdf_path);

        $this->assertNotNull($product->consent_pdf_name);
        $this->assertNotNull($product->guide_pdf_name);

        // Verify document metadata visible in edit page
        $this->actingAs($this->admin)
            ->get("/admin/financing/products/{$product->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Financing/Products/Form', false)
                ->has('product.product_documents', 2)
            );
    }

    public function test_rate_image_upload_persists_and_preview_url_is_available(): void
    {
        $rateImage = UploadedFile::fake()->image('jadual-kadar.png', 800, 600);

        $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Imej Kadar',
                'rate_image' => $rateImage,
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Produk pembiayaan berjaya disimpan.');

        $product = FinancingProduct::query()->where('slug', 'produk-imej-kadar')->firstOrFail();

        Storage::disk('public')->assertExists($product->rate_image_path);

        $this->actingAs($this->admin)
            ->get("/admin/financing/products/{$product->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Financing/Products/Form', false)
                ->where('product.existing_rate_image_url', fn ($value) => $value !== null)
            );
    }

    public function test_admin_can_download_product_document(): void
    {
        Storage::disk('local')->put('financing/product-documents/demo-consent.pdf', 'dokumen-demo-content');

        $this->nonGuaranteedProduct->update([
            'consent_pdf_path' => 'financing/product-documents/demo-consent.pdf',
            'consent_pdf_name' => 'demo-consent.pdf',
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/financing/products/{$this->nonGuaranteedProduct->id}/documents/consent/download")
            ->assertOk()
            ->assertDownload('demo-consent.pdf');
    }

    public function test_admin_cannot_download_product_document_for_other_cooperative(): void
    {
        $otherCoop = Cooperative::factory()->create(['status' => 'active']);
        $otherCategory = FinancingCategory::factory()->create([
            'cooperative_id' => $otherCoop->id,
            'name' => 'Kategori Lain',
            'slug' => 'kategori-lain',
            'type' => FinancingCategoryType::NonGuaranteed->value,
        ]);
        $otherProduct = FinancingProduct::factory()->create([
            'cooperative_id' => $otherCoop->id,
            'financing_category_id' => $otherCategory->id,
            'name' => 'Produk Koperasi Lain',
            'slug' => 'produk-koperasi-lain',
            'requires_guarantor' => false,
            'guarantor_count' => 0,
        ]);

        $this->actingAs($this->admin)
            ->get("/admin/financing/products/{$otherProduct->id}/documents/consent/download")
            ->assertNotFound();
    }

    public function test_borang_permohonan_tab_shows_empty_state_on_create(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/financing/products/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Financing/Products/Form', false)
                ->where('mode', 'create')
                ->where('product', null)
                ->where('productFields', [])
            );
    }

    public function test_admin_can_add_custom_product_field(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/financing/products/{$this->nonGuaranteedProduct->id}/fields", [
                'label' => 'Pernah memohon pembiayaan sebelum ini?',
                'type' => 'yes_no',
                'is_required' => true,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'label' => 'Pernah memohon pembiayaan sebelum ini?',
                'type' => 'yes_no',
                'is_required' => true,
            ]);

        $this->assertDatabaseHas('financing_product_fields', [
            'financing_product_id' => $this->nonGuaranteedProduct->id,
            'label' => 'Pernah memohon pembiayaan sebelum ini?',
        ]);
    }

    public function test_cross_field_validation_rejects_invalid_amount_and_tenure_ranges(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/financing/products/create')
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Ujian Julat',
                'min_amount' => 10000,
                'max_amount' => 1000,
            ])
            ->assertSessionHasErrors('min_amount');

        $this->actingAs($this->admin)
            ->from('/admin/financing/products/create')
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Ujian Julat 2',
                'min_tenure_months' => 48,
                'max_tenure_months' => 12,
            ])
            ->assertSessionHasErrors('min_tenure_months');
    }

    public function test_duplicate_product_name_generates_unique_slug(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Sama Nama',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post('/admin/financing/products', [
                'financing_category_id' => $this->nonGuaranteedCategory->id,
                'unit_id' => $this->loanUnit->id,
                'name' => 'Produk Sama Nama',
                'is_active' => true,
            ])
            ->assertRedirect();

        $first = FinancingProduct::query()->where('slug', 'produk-sama-nama')->first();
        $second = FinancingProduct::query()->where('slug', 'produk-sama-nama-2')->first();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame($first->id, $second->id);
    }
}