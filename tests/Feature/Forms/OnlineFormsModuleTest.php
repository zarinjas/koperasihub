<?php

namespace Tests\Feature\Forms;

use App\Enums\FormFieldType;
use App\Enums\FormStatus;
use App\Enums\FormSubmissionMethod;
use App\Enums\FormSubmissionStatus;
use App\Enums\FormVisibility;
use App\Models\Cooperative;
use App\Models\FormCategory;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\Member;
use App\Models\OnlineForm;
use App\Models\Unit;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OnlineFormsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Cooperative $cooperative;

    protected User $admin;

    protected User $superAdmin;

    protected User $memberUser;

    protected Member $member;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->seed(RolePermissionSeeder::class);

        $this->cooperative = Cooperative::factory()->create(['status' => 'active']);

        $this->admin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $this->admin->assignRole(AccessControl::ROLE_ADMIN);

        $this->superAdmin = User::factory()->admin()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
            'user_type' => AccessControl::ROLE_ADMIN,
        ]);
        $this->superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);

        $this->memberUser = User::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
        ]);
        $this->memberUser->assignRole(AccessControl::ROLE_MEMBER);

        $this->member = Member::factory()->active()->create([
            'cooperative_id' => $this->cooperative->id,
            'user_id' => $this->memberUser->id,
            'full_name' => 'Ahli Demo',
            'email' => $this->memberUser->email,
        ]);

        $this->unit = Unit::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Keanggotaan',
            'slug' => 'unit-keanggotaan',
            'is_active' => true,
        ]);

        $this->admin->update([
            'unit_id' => $this->unit->id,
            'staff_id' => 'STF-001',
            'position_title' => 'Pegawai',
        ]);

        $this->superAdmin->update([
            'unit_id' => $this->unit->id,
            'staff_id' => 'STF-000',
            'position_title' => 'Pengurus Besar',
        ]);
    }

    public function test_admin_can_create_form_category(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/form-categories', [
                'name' => 'Keanggotaan',
                'slug' => 'keanggotaan',
                'description' => 'Kategori untuk urusan keanggotaan.',
                'icon' => 'Users',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('form_categories', [
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Keanggotaan',
            'slug' => 'keanggotaan',
        ]);
    }

    public function test_admin_can_create_form(): void
    {
        $category = $this->createCategory();

        $this->actingAs($this->admin)
            ->post('/admin/forms', [
                'form_category_id' => $category->id,
                'title' => 'Borang Permohonan Contoh',
                'slug' => 'borang-permohonan-contoh',
                'description' => 'Borang rasmi contoh.',
                'visibility' => FormVisibility::Public->value,
                'status' => FormStatus::Draft->value,
                'success_message' => 'Berjaya dihantar.',
                'submission_method' => 'online_only',
                'document_code' => 'FRM/CNT/001',
                'revision_no' => '01',
                'effective_date' => now()->toDateString(),
                'document_title' => 'Borang Permohonan Contoh',
                'show_document_header' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('online_forms', [
            'cooperative_id' => $this->cooperative->id,
            'title' => 'Borang Permohonan Contoh',
            'visibility' => FormVisibility::Public->value,
            'status' => FormStatus::Draft->value,
        ]);
    }

    public function test_admin_can_add_sections(): void
    {
        $form = $this->createForm();

        $this->actingAs($this->admin)
            ->post("/admin/forms/{$form->id}/sections", [
                'title' => 'Maklumat Peribadi',
                'description' => 'Seksyen utama pemohon.',
                'page_break_before' => false,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('form_sections', [
            'online_form_id' => $form->id,
            'title' => 'Maklumat Peribadi',
        ]);
    }

    public function test_admin_can_add_supported_fields(): void
    {
        $form = $this->createForm();
        $section = $this->createSection($form);

        $types = [
            FormFieldType::ShortText,
            FormFieldType::LongText,
            FormFieldType::Email,
            FormFieldType::Phone,
            FormFieldType::IdentityNo,
            FormFieldType::Number,
            FormFieldType::Currency,
            FormFieldType::Date,
            FormFieldType::Select,
            FormFieldType::Radio,
            FormFieldType::Checkbox,
            FormFieldType::YesNo,
            FormFieldType::File,
            FormFieldType::Signature,
            FormFieldType::AgreementCheckbox,
            FormFieldType::Note,
            FormFieldType::InstructionText,
            FormFieldType::OfficeUseBox,
        ];

        foreach ($types as $index => $type) {
            $response = $this->actingAs($this->admin)
                ->post("/admin/forms/{$form->id}/fields", [
                    'form_section_id' => $section->id,
                    'label' => 'Field '.$type->value,
                    'field_key' => 'field_'.$index,
                    'type' => $type->value,
                    'help_text' => 'Catatan demo',
                    'options_text' => in_array($type, [FormFieldType::Select, FormFieldType::Radio, FormFieldType::Checkbox], true) ? "Satu\nDua" : '',
                    'is_required' => $type !== FormFieldType::InstructionText,
                    'settings_json' => ['display_mode' => $type === FormFieldType::OfficeUseBox ? 'print_only' : 'online_and_print'],
                    'is_active' => true,
                ]);

            $response->assertRedirect();
        }

        $this->assertSame($types, FormField::query()->where('online_form_id', $form->id)->orderBy('id')->get()->pluck('type')->all());
    }

    public function test_draft_form_is_not_public(): void
    {
        $form = $this->createForm(status: FormStatus::Draft);

        $this->get("/forms/{$form->slug}")
            ->assertNotFound();
    }

    public function test_archived_form_is_not_public(): void
    {
        $form = $this->createForm(status: FormStatus::Archived);

        $this->get("/forms/{$form->slug}")
            ->assertNotFound();
    }

    public function test_published_public_form_can_be_viewed(): void
    {
        $form = $this->createPublishedForm();

        $this->get("/forms/{$form->slug}")
            ->assertOk()
            ->assertSee($form->title);
    }

    public function test_public_user_can_submit_public_form(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);

        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'submitted_by_email' => 'awam@example.test',
            'answers' => [
                'full_name' => 'Orang Awam',
            ],
        ])
            ->assertRedirect("/forms/{$form->slug}");

        $this->assertDatabaseHas('form_submissions', [
            'online_form_id' => $form->id,
            'submitted_by_name' => 'Orang Awam',
        ]);
    }

    public function test_members_only_form_redirects_guest_to_login(): void
    {
        $form = $this->createPublishedForm(visibility: FormVisibility::MembersOnly);

        $this->get("/forms/{$form->slug}")
            ->assertRedirect('/member/login');
    }

    public function test_logged_in_member_can_submit_members_only_form(): void
    {
        $form = $this->createPublishedForm(visibility: FormVisibility::MembersOnly);
        $section = $this->createSection($form);
        $this->createField($form, $section, 'No. telefon', 'phone', FormFieldType::Phone);

        $this->actingAs($this->memberUser)
            ->post("/forms/{$form->slug}", [
                'answers' => [
                    'phone' => '0123456789',
                ],
            ])
            ->assertRedirect("/forms/{$form->slug}");

        $this->assertDatabaseHas('form_submissions', [
            'online_form_id' => $form->id,
            'member_id' => $this->member->id,
        ]);
    }

    public function test_required_validation_works(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);

        $this->from("/forms/{$form->slug}")
            ->post("/forms/{$form->slug}", [
                'submitted_by_name' => 'Orang Awam',
                'answers' => [
                    'full_name' => '',
                ],
            ])
            ->assertRedirect("/forms/{$form->slug}")
            ->assertSessionHasErrors('answers.full_name');
    }

    public function test_agreement_checkbox_required_validation_works(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Akuan', 'agreement', FormFieldType::AgreementCheckbox, true, 'Saya setuju.');

        $this->from("/forms/{$form->slug}")
            ->post("/forms/{$form->slug}", [
                'submitted_by_name' => 'Orang Awam',
                'answers' => [],
            ])
            ->assertRedirect("/forms/{$form->slug}")
            ->assertSessionHasErrors('answers.agreement');
    }

    public function test_file_validation_rejects_invalid_type(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Lampiran', 'attachment', FormFieldType::File);

        $this->from("/forms/{$form->slug}")
            ->post("/forms/{$form->slug}", [
                'submitted_by_name' => 'Orang Awam',
                'files' => [
                    'attachment' => UploadedFile::fake()->create('fail.txt', 20, 'text/plain'),
                ],
            ])
            ->assertRedirect("/forms/{$form->slug}")
            ->assertSessionHasErrors('files.attachment');
    }

    public function test_signature_field_can_be_submitted_and_reference_number_is_generated(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Tandatangan', 'signature', FormFieldType::Signature);

        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'answers' => [
                'signature' => $this->signatureDataUrl(),
            ],
        ])->assertRedirect("/forms/{$form->slug}");

        $submission = $form->submissions()->first();

        $this->assertNotNull($submission);
        $this->assertMatchesRegularExpression('/^FRM-\d{8}-\d{4}$/', $submission->reference_no);
        $this->assertDatabaseHas('form_submission_files', [
            'form_submission_id' => $submission->id,
            'field_key' => 'signature',
            'is_signature' => true,
        ]);
    }

    public function test_reference_number_generation_skips_soft_deleted_sequence(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);
        $deletedSubmission = $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'member_id' => $this->member->id,
            'reference_no' => 'FRM-'.now()->format('Ymd').'-0001',
            'submitted_by_name' => 'Ahli Demo',
            'submitted_by_email' => $this->memberUser->email,
            'data_json' => [
                'full_name' => [
                    'type' => FormFieldType::ShortText->value,
                    'label' => 'Nama penuh',
                    'value' => 'Ahli Demo',
                ],
            ],
            'status' => FormSubmissionStatus::Submitted->value,
            'submitted_at' => now(),
        ]);
        $deletedSubmission->delete();
        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'answers' => [
                'full_name' => 'Orang Awam',
            ],
        ])->assertRedirect("/forms/{$form->slug}");

        $latestSubmission = $form->submissions()->latest('id')->first();

        $this->assertNotNull($latestSubmission);
        $this->assertNotSame($deletedSubmission->reference_no, $latestSubmission->reference_no);
        $this->assertStringEndsWith('-0002', $latestSubmission->reference_no);
    }

    public function test_admin_can_view_submission_detail_and_print_preview(): void
    {
        $form = $this->createPublishedForm();
        $submission = $this->createSubmission($form);

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions/{$submission->id}")
            ->assertOk()
            ->assertSee($submission->reference_no);

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions/{$submission->id}/print")
            ->assertOk()
            ->assertSee($submission->reference_no);
    }

    public function test_admin_navigation_shows_borang_online_children_when_form_permissions_exist(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Dashboard', false)
                ->where('navigation.admin.6.label', 'Borang Online')
                ->where('navigation.admin.6.href', route('admin.forms.index'))
                ->where('navigation.admin.6.icon', 'ClipboardList')
                ->where('navigation.admin.6.children.0.label', 'Permohonan Borang')
            );
    }

    public function test_admin_and_super_admin_can_access_form_management_pages(): void
    {
        $category = $this->createCategory();
        $form = $this->createPublishedForm(category: $category);
        $submission = $this->createSubmission($form);

        $this->actingAs($this->admin)->get('/admin/form-categories')->assertOk();
        $this->actingAs($this->admin)->get('/admin/forms')->assertOk();
        $this->actingAs($this->admin)->get("/admin/forms/{$form->id}/submissions")->assertOk();

        $this->actingAs($this->superAdmin)->get('/admin/form-categories')->assertOk();
        $this->actingAs($this->superAdmin)->get('/admin/forms')->assertOk();
        $this->actingAs($this->superAdmin)->get("/admin/forms/{$form->id}/submissions/{$submission->id}")->assertOk();
    }

    public function test_member_cannot_access_admin_form_pages(): void
    {
        $category = $this->createCategory();
        $form = $this->createPublishedForm(category: $category);
        $submission = $this->createSubmission($form);

        $this->actingAs($this->memberUser)
            ->get('/admin/form-categories')
            ->assertRedirect('/member/dashboard');

        $this->actingAs($this->memberUser)
            ->get('/admin/forms')
            ->assertRedirect('/member/dashboard');

        $this->actingAs($this->memberUser)
            ->get("/admin/forms/{$form->id}/submissions/{$submission->id}")
            ->assertRedirect('/member/dashboard');
    }

    public function test_preview_pdf_page_renders_blank_form_template(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/preview-pdf")
            ->assertOk()
            ->assertSee($form->title)
            ->assertSee('Cetak / Simpan sebagai PDF');
    }

    public function test_print_preview_renders_note_and_office_use_box(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form, 'Pengesahan');
        $this->createField($form, $section, 'Nota Penting', 'important_note', FormFieldType::Note, false, 'Sila gunakan dakwat hitam.');
        $this->createField($form, $section, 'Ruang Pejabat', 'office_box', FormFieldType::OfficeUseBox, false, 'Untuk cop rasmi.');

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/preview-pdf")
            ->assertOk()
            ->assertSee('Nota Penting')
            ->assertSee('Sila gunakan dakwat hitam.')
            ->assertSee('office-use-box', false);
    }

    public function test_print_only_office_use_box_hidden_online_but_visible_in_print_views(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Ruang Pejabat', 'office_box', FormFieldType::OfficeUseBox, false, 'Untuk kegunaan pejabat.');

        $this->get("/forms/{$form->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Pages/Forms/Show', false)
                ->where('formRecord.sections.0.fields.0.display_mode', 'print_only')
            );

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/preview-pdf")
            ->assertOk()
            ->assertSee('Ruang Pejabat');
    }

    public function test_admin_can_save_section_as_template_and_copy_it_into_form(): void
    {
        $form = $this->createForm();
        $section = $this->createSection($form, 'Maklumat Waris');
        $this->createField($form, $section, 'Nama Waris', 'nominee_name', FormFieldType::ShortText);

        $this->actingAs($this->admin)
            ->post("/admin/forms/{$form->id}/sections/{$section->id}/save-template")
            ->assertRedirect();

        $this->assertDatabaseHas('form_section_templates', [
            'cooperative_id' => $this->cooperative->id,
            'title' => 'Maklumat Waris',
        ]);

        $templateId = \App\Models\FormSectionTemplate::query()->value('id');

        $this->actingAs($this->admin)
            ->post("/admin/forms/{$form->id}/sections/from-template", [
                'template_ref' => 'saved:'.$templateId,
            ])
            ->assertRedirect();

        $this->assertSame(2, $form->sections()->count());
        $this->assertDatabaseCount('form_fields', 2);
    }

    public function test_category_page_shows_published_forms_and_forms_index_shows_active_categories(): void
    {
        $activeCategory = $this->createCategory('Keanggotaan', 'keanggotaan', true);
        $inactiveCategory = $this->createCategory('Arkib', 'arkib', false);

        $published = $this->createPublishedForm(category: $activeCategory, title: 'Borang Awam');
        $this->createForm(category: $activeCategory, title: 'Borang Draf', status: FormStatus::Draft);
        $this->createPublishedForm(category: $inactiveCategory, title: 'Borang Tidak Patut Dipapar');

        $this->get('/forms')
            ->assertOk()
            ->assertSee($activeCategory->name)
            ->assertDontSee($inactiveCategory->name);

        $this->get("/forms/category/{$activeCategory->slug}")
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Borang Draf');
    }

    public function test_inactive_category_page_is_not_publicly_accessible(): void
    {
        $inactiveCategory = $this->createCategory('Arkib', 'arkib', false);
        $this->createPublishedForm(category: $inactiveCategory, title: 'Borang Tidak Patut Dipapar');

        $this->get("/forms/category/{$inactiveCategory->slug}")
            ->assertNotFound();
    }

    public function test_inactive_section_fields_are_ignored_during_public_validation_and_storage(): void
    {
        $form = $this->createPublishedForm();
        $activeSection = $this->createSection($form, 'Seksyen Aktif');
        $inactiveSection = $this->createSection($form, 'Seksyen Tidak Aktif', false);

        $this->createField($form, $activeSection, 'Nama penuh', 'full_name', FormFieldType::ShortText);
        $this->createField($form, $inactiveSection, 'No. KP', 'identity_no', FormFieldType::IdentityNo);

        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'answers' => [
                'full_name' => 'Orang Awam',
            ],
        ])->assertRedirect("/forms/{$form->slug}");

        $submission = $form->submissions()->latest('id')->first();

        $this->assertNotNull($submission);
        $this->assertArrayHasKey('full_name', $submission->data_json);
        $this->assertArrayNotHasKey('identity_no', $submission->data_json);
    }

    private function createCategory(string $name = 'Keanggotaan', string $slug = 'keanggotaan', bool $active = true, ?int $unitId = null): FormCategory
    {
        return FormCategory::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $unitId ?? $this->unit?->id,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Kategori borang demo.',
            'icon' => 'FileText',
            'is_active' => $active,
        ]);
    }

    private function createForm(
        ?FormCategory $category = null,
        string $title = 'Borang Demo',
        FormStatus $status = FormStatus::Draft,
        FormVisibility $visibility = FormVisibility::Public,
    ): OnlineForm {
        $category ??= $this->createCategory();

        return OnlineForm::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'form_category_id' => $category->id,
            'created_by' => $this->admin->id,
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'description' => 'Borang demo.',
            'visibility' => $visibility->value,
            'status' => $status->value,
            'success_message' => 'Borang anda berjaya dihantar.',
            'submission_method' => FormSubmissionMethod::OnlineOnly->value,
            'document_code' => 'FRM/DEMO/001',
            'revision_no' => '01',
            'effective_date' => now()->toDateString(),
            'document_title' => $title,
            'show_document_header' => true,
        ]);
    }

    private function createPublishedForm(?FormCategory $category = null, string $title = 'Borang Demo', FormVisibility $visibility = FormVisibility::Public): OnlineForm
    {
        return $this->createForm($category, $title, FormStatus::Published, $visibility);
    }

    private function createSection(OnlineForm $form, string $title = 'Maklumat Peribadi', bool $active = true): FormSection
    {
        return FormSection::query()->create([
            'online_form_id' => $form->id,
            'title' => $title,
            'description' => 'Seksyen demo.',
            'page_break_before' => false,
            'is_active' => $active,
        ]);
    }

    private function createField(
        OnlineForm $form,
        FormSection $section,
        string $label,
        string $key,
        FormFieldType $type,
        bool $required = true,
        ?string $helpText = null,
    ): FormField {
        return FormField::query()->create([
            'online_form_id' => $form->id,
            'form_section_id' => $section->id,
            'label' => $label,
            'field_key' => $key,
            'type' => $type->value,
            'placeholder' => null,
            'help_text' => $helpText,
            'is_required' => $required,
            'options_json' => in_array($type, [FormFieldType::Select, FormFieldType::Radio, FormFieldType::Checkbox], true) ? ['Satu', 'Dua'] : [],
            'validation_json' => [],
            'settings_json' => $type === FormFieldType::OfficeUseBox
                ? ['print_only' => true, 'display_mode' => 'print_only']
                : ['display_mode' => 'online_and_print'],
            'is_active' => true,
        ]);
    }

    private function createSubmission(OnlineForm $form)
    {
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);
        $category = $form->category;

        return $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $category?->unit_id,
            'unit_name_snapshot' => $category?->unit?->name,
            'member_id' => $this->member->id,
            'reference_no' => 'FRM-20260505-0001',
            'submitted_by_name' => 'Ahli Demo',
            'submitted_by_email' => $this->memberUser->email,
            'data_json' => [
                'full_name' => [
                    'type' => FormFieldType::ShortText->value,
                    'label' => 'Nama penuh',
                    'value' => 'Ahli Demo',
                ],
            ],
            'status' => FormSubmissionStatus::Submitted->value,
            'submitted_at' => now(),
        ]);
    }

    private function signatureDataUrl(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9s2son8AAAAASUVORK5CYII=';
    }

    // --- Hybrid Submission Method Tests ---

    public function test_existing_forms_default_to_online_only(): void
    {
        $form = $this->createForm();

        $this->assertSame(FormSubmissionMethod::OnlineOnly, $form->submission_method);
    }

    public function test_admin_can_set_submission_method_to_requires_stamped_upload(): void
    {
        $category = $this->createCategory();
        $form = $this->createForm(category: $category);

        $this->actingAs($this->admin)
            ->patch("/admin/forms/{$form->id}", array_merge($this->baseFormPayload($category), [
                'submission_method' => 'requires_stamped_upload',
                'stamped_upload_instructions' => 'Sila dapatkan cop jabatan.',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('online_forms', [
            'id' => $form->id,
            'submission_method' => 'requires_stamped_upload',
            'stamped_upload_instructions' => 'Sila dapatkan cop jabatan.',
        ]);
    }

    public function test_online_only_form_submits_directly_as_submitted(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::OnlineOnly);
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);

        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'answers' => ['full_name' => 'Orang Awam'],
        ])->assertRedirect("/forms/{$form->slug}");

        $submission = $form->submissions()->latest('id')->first();
        $this->assertNotNull($submission);
        $this->assertSame(FormSubmissionStatus::Submitted, $submission->status);
    }

    public function test_requires_stamped_upload_form_creates_pending_stamp_upload_submission(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);

        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'answers' => ['full_name' => 'Orang Awam'],
        ])->assertRedirect();

        $submission = $form->submissions()->latest('id')->first();
        $this->assertNotNull($submission);
        $this->assertSame(FormSubmissionStatus::PendingStampUpload, $submission->status);
    }

    public function test_pending_stamp_upload_submission_shows_next_step_page(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);

        $submission = $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'reference_no' => 'FRM-20260505-9001',
            'submitted_by_name' => 'Orang Awam',
            'data_json' => [],
            'status' => FormSubmissionStatus::PendingStampUpload->value,
            'submitted_at' => now(),
        ]);

        session()->put("form_submission.{$submission->id}", true);

        $this->get("/forms/{$form->slug}/submission/{$submission->id}/next-step")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Pages/Forms/NextStep', false)
                ->where('submission.reference_no', 'FRM-20260505-9001')
            );
    }

    public function test_stamped_upload_changes_status_to_submitted(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);

        $submission = $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'reference_no' => 'FRM-20260505-9002',
            'submitted_by_name' => 'Orang Awam',
            'data_json' => [],
            'status' => FormSubmissionStatus::PendingStampUpload->value,
            'submitted_at' => now(),
        ]);

        session()->put("form_submission.{$submission->id}", true);

        $file = UploadedFile::fake()->create('borang_bercop.pdf', 500, 'application/pdf');

        $this->post("/forms/{$form->slug}/submission/{$submission->id}/upload-stamped", [
            'stamped_file' => $file,
        ])->assertRedirect("/forms/{$form->slug}");

        $submission->refresh();
        $this->assertSame(FormSubmissionStatus::Submitted, $submission->status);
        $this->assertNotNull($submission->stamped_file_path);
        $this->assertSame('borang_bercop.pdf', $submission->stamped_file_original_name);
        $this->assertNotNull($submission->stamped_file_uploaded_at);
    }

    public function test_invalid_stamped_file_type_is_rejected(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);

        $submission = $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'reference_no' => 'FRM-20260505-9003',
            'submitted_by_name' => 'Orang Awam',
            'data_json' => [],
            'status' => FormSubmissionStatus::PendingStampUpload->value,
            'submitted_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->post("/forms/{$form->slug}/submission/{$submission->id}/upload-stamped", [
            'stamped_file' => $file,
        ])->assertSessionHasErrors('stamped_file');

        $submission->refresh();
        $this->assertSame(FormSubmissionStatus::PendingStampUpload, $submission->status);
        $this->assertNull($submission->stamped_file_path);
    }

    public function test_stamped_file_is_stored_on_local_disk(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);

        $submission = $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'reference_no' => 'FRM-20260505-9004',
            'submitted_by_name' => 'Orang Awam',
            'data_json' => [],
            'status' => FormSubmissionStatus::PendingStampUpload->value,
            'submitted_at' => now(),
        ]);

        session()->put("form_submission.{$submission->id}", true);

        $file = UploadedFile::fake()->create('cop.jpg', 200, 'image/jpeg');

        $this->post("/forms/{$form->slug}/submission/{$submission->id}/upload-stamped", [
            'stamped_file' => $file,
        ])->assertRedirect();

        $submission->refresh();
        Storage::disk('local')->assertExists($submission->stamped_file_path);
    }

    public function test_public_cannot_access_stamped_file_directly(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);
        $submission = $this->createStampedSubmission($form);

        $this->get("/admin/forms/{$form->id}/submissions/{$submission->id}/stamped-file/download")
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_stamped_upload_status_in_submission_detail(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);
        $submission = $this->createStampedSubmission($form);

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions/{$submission->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Forms/Submissions/Show', false)
                ->where('submissionRecord.has_stamped_file', true)
                ->where('submissionRecord.stamped_file_original_name', 'borang_bercop.pdf')
                ->where('submissionRecord.submission_method', 'requires_stamped_upload')
            );
    }

    public function test_admin_can_filter_submissions_by_status(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);
        $this->createSubmissionWithStatus($form, FormSubmissionStatus::PendingStampUpload, 'FRM-20260505-A001');
        $this->createSubmissionWithStatus($form, FormSubmissionStatus::Submitted, 'FRM-20260505-A002');

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions?status=pending_stamp_upload")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Forms/Submissions/Index', false)
                ->where('submissions.total', 1)
            );
    }

    public function test_admin_can_filter_submissions_by_stamped_state(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);
        $this->createSubmissionWithStatus($form, FormSubmissionStatus::PendingStampUpload, 'FRM-20260505-B001');
        $withStamp = $this->createStampedSubmission($form, 'FRM-20260505-B002');

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions?stamped_state=uploaded")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Forms/Submissions/Index', false)
                ->where('submissions.total', 1)
            );

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions?stamped_state=missing")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Pages/Forms/Submissions/Index', false)
                ->where('submissions.total', 1)
            );
    }

    public function test_draft_form_with_stamped_method_cannot_be_submitted(): void
    {
        $form = $this->createForm();
        $form->update(['submission_method' => 'requires_stamped_upload']);
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);

        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'answers' => ['full_name' => 'Orang Awam'],
        ])->assertForbidden();
    }

    public function test_member_user_cannot_access_admin_submissions_with_stamped_filter(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);

        $this->actingAs($this->memberUser)
            ->get("/admin/forms/{$form->id}/submissions?stamped_state=uploaded")
            ->assertRedirect('/member/dashboard');
    }

    public function test_admin_can_download_stamped_file(): void
    {
        $form = $this->createPublishedFormWithMethod(FormSubmissionMethod::RequiresStampedUpload);
        $submission = $this->createStampedSubmission($form);

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions/{$submission->id}/stamped-file/download")
            ->assertOk();
    }

    private function createPublishedFormWithMethod(FormSubmissionMethod $method, ?FormCategory $category = null): OnlineForm
    {
        $category ??= $this->createCategory();

        return OnlineForm::query()->create([
            'cooperative_id' => $this->cooperative->id,
            'form_category_id' => $category->id,
            'created_by' => $this->admin->id,
            'title' => 'Borang Hybrid Demo',
            'slug' => 'borang-hybrid-demo-'.uniqid(),
            'description' => 'Borang demo kaedah hybrid.',
            'visibility' => FormVisibility::Public->value,
            'status' => FormStatus::Published->value,
            'submission_method' => $method->value,
            'stamped_upload_instructions' => 'Sila dapatkan cop dan tandatangan pengesahan.',
            'success_message' => 'Berjaya.',
            'document_code' => null,
            'revision_no' => null,
            'effective_date' => null,
            'document_title' => null,
            'show_document_header' => false,
        ]);
    }

    private function createStampedSubmission(OnlineForm $form, string $referenceNo = 'FRM-20260505-9099'): \App\Models\FormSubmission
    {
        Storage::disk('local')->put("forms/stamped/999/borang_bercop.pdf", 'fake pdf content');
        $category = $form->category;

        return $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $category?->unit_id,
            'unit_name_snapshot' => $category?->unit?->name,
            'reference_no' => $referenceNo,
            'submitted_by_name' => 'Orang Awam',
            'data_json' => [],
            'status' => FormSubmissionStatus::Submitted->value,
            'stamped_file_path' => 'forms/stamped/999/borang_bercop.pdf',
            'stamped_file_original_name' => 'borang_bercop.pdf',
            'stamped_file_uploaded_at' => now(),
            'submitted_at' => now(),
        ]);
    }

    private function createSubmissionWithStatus(OnlineForm $form, FormSubmissionStatus $status, string $referenceNo): \App\Models\FormSubmission
    {
        $category = $form->category;

        return $form->submissions()->create([
            'cooperative_id' => $this->cooperative->id,
            'unit_id' => $category?->unit_id,
            'unit_name_snapshot' => $category?->unit?->name,
            'reference_no' => $referenceNo,
            'submitted_by_name' => 'Orang Awam',
            'data_json' => [],
            'status' => $status->value,
            'submitted_at' => now(),
        ]);
    }

    private function baseFormPayload(FormCategory $category): array
    {
        return [
            'form_category_id' => $category->id,
            'title' => 'Borang Demo',
            'slug' => 'borang-demo',
            'description' => 'Penerangan.',
            'visibility' => FormVisibility::Public->value,
            'status' => FormStatus::Draft->value,
            'success_message' => 'Berjaya.',
            'submission_method' => 'online_only',
            'stamped_upload_instructions' => null,
            'document_code' => null,
            'revision_no' => null,
            'effective_date' => null,
            'document_title' => null,
            'show_document_header' => false,
        ];
    }

    // --- Unit Awareness Tests ---

    public function test_category_can_be_assigned_unit(): void
    {
        $unitB = Unit::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Pinjaman',
            'slug' => 'unit-pinjaman',
            'is_active' => true,
        ]);

        $category = $this->createCategory('Pembiayaan', 'pembiayaan', unitId: $unitB->id);

        $this->assertSame($unitB->id, $category->unit_id);
    }

    public function test_submission_stores_unit_snapshot_on_submit(): void
    {
        $form = $this->createPublishedForm();
        $section = $this->createSection($form);
        $this->createField($form, $section, 'Nama penuh', 'full_name', FormFieldType::ShortText);

        $this->post("/forms/{$form->slug}", [
            'submitted_by_name' => 'Orang Awam',
            'answers' => ['full_name' => 'Orang Awam'],
        ])->assertRedirect();

        $submission = $form->submissions()->latest('id')->first();
        $this->assertNotNull($submission);
        $this->assertSame($this->unit->id, $submission->unit_id);
        $this->assertSame('Unit Keanggotaan', $submission->unit_name_snapshot);
    }

    public function test_super_admin_can_view_all_submissions_across_units(): void
    {
        $unitB = Unit::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Pinjaman',
            'slug' => 'unit-pinjaman',
            'is_active' => true,
        ]);

        $categoryA = $this->createCategory();
        $categoryB = $this->createCategory('Pembiayaan', 'pembiayaan', unitId: $unitB->id);

        $formA = $this->createPublishedForm($categoryA, 'Borang A');
        $formB = $this->createPublishedForm($categoryB, 'Borang B');

        $this->createSubmissionWithStatus($formA, FormSubmissionStatus::Submitted, 'FRM-20260505-0101');
        $this->createSubmissionWithStatus($formB, FormSubmissionStatus::Submitted, 'FRM-20260505-0102');

        $this->actingAs($this->superAdmin)
            ->get('/admin/form-submissions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('submissions.data', 2)
            );
    }

    public function test_admin_can_view_own_unit_submissions(): void
    {
        $category = $this->createCategory();
        $form = $this->createPublishedForm($category, 'Borang Unit Saya');

        $this->createSubmissionWithStatus($form, FormSubmissionStatus::Submitted, 'FRM-20260505-0201');

        $this->actingAs($this->admin)
            ->get('/admin/form-submissions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('submissions.data', 1)
            );
    }

    public function test_admin_cannot_view_other_unit_submission_detail(): void
    {
        $unitB = Unit::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Pinjaman',
            'slug' => 'unit-pinjaman',
            'is_active' => true,
        ]);

        $category = $this->createCategory('Pembiayaan', 'pembiayaan', unitId: $unitB->id);
        $form = $this->createPublishedForm($category, 'Borang Pinjaman');
        $submission = $this->createSubmissionWithStatus($form, FormSubmissionStatus::Submitted, 'FRM-20260505-0301');

        $this->actingAs($this->admin)
            ->get("/admin/forms/{$form->id}/submissions/{$submission->id}")
            ->assertForbidden();
    }

    public function test_admin_cannot_update_status_for_other_unit_submission(): void
    {
        $unitB = Unit::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Pinjaman',
            'slug' => 'unit-pinjaman',
            'is_active' => true,
        ]);

        $category = $this->createCategory('Pembiayaan', 'pembiayaan', unitId: $unitB->id);
        $form = $this->createPublishedForm($category, 'Borang Pinjaman');
        $submission = $this->createSubmissionWithStatus($form, FormSubmissionStatus::Submitted, 'FRM-20260505-0401');

        $this->actingAs($this->admin)
            ->patch("/admin/forms/{$form->id}/submissions/{$submission->id}", [
                'status' => FormSubmissionStatus::Approved->value,
                'admin_notes' => 'Tidak sah.',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_filter_submissions_by_unit(): void
    {
        $unitB = Unit::factory()->create([
            'cooperative_id' => $this->cooperative->id,
            'name' => 'Unit Pinjaman',
            'slug' => 'unit-pinjaman',
            'is_active' => true,
        ]);

        $categoryA = $this->createCategory();
        $categoryB = $this->createCategory('Pembiayaan', 'pembiayaan', unitId: $unitB->id);

        $formA = $this->createPublishedForm($categoryA, 'Borang A');
        $formB = $this->createPublishedForm($categoryB, 'Borang B');

        $this->createSubmissionWithStatus($formA, FormSubmissionStatus::Submitted, 'FRM-20260505-0501');
        $this->createSubmissionWithStatus($formB, FormSubmissionStatus::Submitted, 'FRM-20260505-0502');

        $this->actingAs($this->superAdmin)
            ->get('/admin/form-submissions?unit='.$unitB->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('submissions.data', 1)
                ->where('submissions.data.0.reference_no', 'FRM-20260505-0502')
            );
    }

    public function test_member_sees_only_own_submissions_in_portal(): void
    {
        $category = $this->createCategory();
        $form = $this->createPublishedForm($category, 'Borang Portal');

        $this->createSubmissionWithStatus($form, FormSubmissionStatus::Submitted, 'FRM-20260505-0601');

        $this->actingAs($this->memberUser)
            ->get('/member/applications')
            ->assertOk();
    }
}