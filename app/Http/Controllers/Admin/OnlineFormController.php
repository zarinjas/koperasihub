<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FormFieldType;
use App\Enums\FormStatus;
use App\Enums\FormSubmissionMethod;
use App\Enums\FormSubmissionStatus;
use App\Enums\FormVisibility;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOnlineFormRequest;
use App\Http\Requests\Admin\UpdateOnlineFormRequest;
use App\Models\FormCategory;
use App\Models\FormField;
use App\Models\OnlineForm;
use App\Services\AuditLogService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OnlineFormController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $visibility = $request->string('visibility')->toString();
        $category = $request->integer('category');
        $tab = $request->string('tab', 'borang')->toString();

        $forms = OnlineForm::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->with('category')
            ->withCount('submissions')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('document_code', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, FormStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($visibility, FormVisibility::values(), true), fn ($query) => $query->where('visibility', $visibility))
            ->when($category > 0, fn ($query) => $query->where('form_category_id', $category))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (OnlineForm $form) => $this->serializeForm($form));

        $categories = FormCategory::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->withCount(['forms as published_forms_count' => fn ($query) => $query->where('status', FormStatus::Published->value)])
            ->latest()
            ->get()
            ->map(fn (FormCategory $fc) => [
                'id' => $fc->id,
                'name' => $fc->name,
                'description' => $fc->description,
                'is_active' => $fc->is_active,
                'published_forms_count' => $fc->published_forms_count,
            ])
            ->all();

        return Inertia::render('Admin/Pages/Forms/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'visibility' => $visibility,
                'category' => $category ?: '',
                'tab' => $tab,
            ],
            'forms' => $forms,
            'categoryOptions' => $this->categoryOptions(includeAll: true),
            'statusOptions' => $this->statusOptions(includeAll: true),
            'visibilityOptions' => $this->visibilityOptions(includeAll: true),
            'categories' => $categories,
            'canDeleteForm' => $request->user()?->can(AccessControl::PERMISSION_DELETE_FORMS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Forms/Form', [
            'mode' => 'create',
            'formRecord' => null,
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
            'submissionMethodOptions' => $this->submissionMethodOptions(),
            'fieldTypeOptions' => $this->fieldTypeOptions(),
            'fieldTypeConfigs' => $this->fieldTypeConfigs(),
            'sections' => [],
            'sectionOptions' => [],
        ]);
    }

    public function edit(OnlineForm $onlineForm): Response
    {
        $this->ensureSameCooperative($onlineForm);

        $onlineForm->load([
            'sections' => fn ($query) => $query->latest()->orderBy('id'),
            'sections.fields' => fn ($query) => $query->latest()->orderBy('id'),
        ]);

        $sections = $onlineForm->sections->map(fn ($section) => [
            'id' => $section->id,
            'title' => $section->title,
            'description' => $section->description,
            'page_break_before' => $section->page_break_before,
            'is_active' => $section->is_active,
            'fields_count' => $section->fields->count(),
            'fields' => $section->fields->map(fn (FormField $field) => [
                'id' => $field->id,
                'label' => $field->label,
                'type' => $field->type->value,
                'type_label' => $this->fieldTypeLabel($field->type),
                'is_required' => $field->is_required,
                'is_active' => $field->is_active,
                'form_section_id' => $field->form_section_id,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'options_json' => $field->options_json ?? [],
                'options_text' => implode("\n", $field->options_json ?? []),
                'file_max_size_kb' => $field->validation_json['max_size_kb'] ?? 5120,
                'print_only' => $field->settings_json['print_only'] ?? false,
                'display_mode' => $field->displayMode()->value,
                'validation_json' => $field->validation_json ?? [],
                'settings_json' => $field->settings_json ?? [],
                'agreement_text' => $field->help_text ?? '',
            ])->all(),
        ])->all();

        $sectionOptions = $onlineForm->sections->map(fn ($section) => [
            'value' => $section->id,
            'label' => $section->title,
        ])->all();

        return Inertia::render('Admin/Pages/Forms/Form', [
            'mode' => 'edit',
            'formRecord' => $this->serializeForm($onlineForm->loadCount('submissions')),
            'categoryOptions' => $this->categoryOptions(),
            'statusOptions' => $this->statusOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
            'submissionMethodOptions' => $this->submissionMethodOptions(),
            'fieldTypeOptions' => $this->fieldTypeOptions(),
            'fieldTypeConfigs' => $this->fieldTypeConfigs(),
            'sections' => $sections,
            'sectionOptions' => $sectionOptions,
        ]);
    }

    public function store(StoreOnlineFormRequest $request): RedirectResponse
    {
        $form = OnlineForm::query()->create([
            ...$request->validated(),
            'cooperative_id' => $this->activeCooperative()?->id,
            'created_by' => $request->user()?->id,
        ]);

        $this->auditLog->record('online_form.created', $form, newValues: $form->toArray());

        return redirect()
            ->route('admin.forms.edit', $form)
            ->with('status', 'Borang berjaya dicipta.');
    }

    public function update(UpdateOnlineFormRequest $request, OnlineForm $onlineForm): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        $old = $onlineForm->toArray();
        $onlineForm->update($request->validated());

        $this->auditLog->record('online_form.updated', $onlineForm, $old, $onlineForm->fresh()->toArray());

        return back()->with('status', 'Borang berjaya dikemas kini.');
    }

    public function publish(OnlineForm $onlineForm): RedirectResponse
    {
        return $this->changeStatus($onlineForm, FormStatus::Published, 'Borang berjaya diterbitkan.');
    }

    public function unpublish(OnlineForm $onlineForm): RedirectResponse
    {
        return $this->changeStatus($onlineForm, FormStatus::Draft, 'Borang dikembalikan ke status draf.');
    }

    public function archive(OnlineForm $onlineForm): RedirectResponse
    {
        return $this->changeStatus($onlineForm, FormStatus::Archived, 'Borang berjaya diarkibkan.');
    }

    public function destroy(OnlineForm $onlineForm): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);

        $terminalStatuses = [
            FormSubmissionStatus::Approved->value,
            FormSubmissionStatus::Rejected->value,
            FormSubmissionStatus::Closed->value,
        ];

        $pendingCount = $onlineForm->submissions()
            ->whereNotIn('status', $terminalStatuses)
            ->count();

        if ($pendingCount > 0) {
            return back()->with('error', 'Borang tidak boleh dipadam kerana masih terdapat ' . $pendingCount . ' permohonan yang belum selesai. Arkibkan borang ini jika tidak diperlukan lagi.');
        }

        $totalSubmissions = $onlineForm->submissions()->count();
        $this->auditLog->record('online_form.deleted', $onlineForm, $onlineForm->toArray());
        $onlineForm->delete();

        return redirect()
            ->route('admin.forms.index')
            ->with('status', 'Borang berjaya dipadam.' . ($totalSubmissions > 0 ? " {$totalSubmissions} permohonan turut diarkibkan bersama." : ''));
    }

    public function previewPdf(OnlineForm $onlineForm)
    {
        $this->ensureSameCooperative($onlineForm);
        $onlineForm->load([
            'category',
            'sections' => fn ($query) => $query->latest()->orderBy('id'),
            'sections.fields' => fn ($query) => $query->latest()->orderBy('id'),
        ]);

        $cooperative = $this->activeCooperative();
        $logoUrl = $cooperative?->logo_path ? Storage::disk('public')->url($cooperative->logo_path) : null;

        return response()->view('forms.preview-template', [
            'cooperative' => $cooperative,
            'logoUrl' => $logoUrl,
            'form' => $onlineForm,
            'sections' => $onlineForm->sections,
            'printUrl' => route('admin.forms.preview-pdf', $onlineForm),
            'backUrl' => route('admin.forms.edit', $onlineForm),
        ]);
    }

    private function changeStatus(OnlineForm $onlineForm, FormStatus $status, string $message): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        $old = $onlineForm->toArray();

        $onlineForm->update([
            'status' => $status->value,
        ]);

        $this->auditLog->record('online_form.status_changed', $onlineForm, $old, $onlineForm->fresh()->toArray());

        return back()->with('status', $message);
    }

    private function serializeForm(OnlineForm $form): array
    {
        return [
            'id' => $form->id,
            'title' => $form->title,
            'slug' => $form->slug,
            'description' => $form->description,
            'form_category_id' => $form->form_category_id,
            'category_name' => $form->category?->name,
            'visibility' => $form->visibility->value,
            'status' => $form->status->value,
            'success_message' => $form->success_message,
            'submission_method' => $form->submission_method->value,
            'stamped_upload_instructions' => $form->stamped_upload_instructions,
            'document_code' => $form->document_code,
            'revision_no' => $form->revision_no,
            'effective_date' => $form->effective_date?->format('Y-m-d'),
            'document_title' => $form->document_title,
            'show_document_header' => $form->show_document_header,
            'submissions_count' => $form->submissions_count,
            'public_url' => route('public.forms.show', $form->slug),
            'preview_pdf_url' => route('admin.forms.preview-pdf', $form),
            'sections_url' => route('admin.forms.sections.index', $form),
            'fields_url' => route('admin.forms.fields.index', $form),
            'submissions_url' => route('admin.forms.submissions.index', $form),
            'updated_at' => $form->updated_at?->format('d/m/Y H:i'),
        ];
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = FormCategory::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->latest()
            ->get()
            ->map(fn (FormCategory $category) => [
                'value' => $category->id,
                'label' => $category->name,
            ])
            ->all();

        return $includeAll
            ? [['value' => '', 'label' => 'Semua kategori'], ...$options]
            : [['value' => '', 'label' => 'Tanpa kategori'], ...$options];
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => FormStatus::Draft->value, 'label' => 'Draf'],
            ['value' => FormStatus::Published->value, 'label' => 'Diterbitkan'],
            ['value' => FormStatus::Archived->value, 'label' => 'Diarkibkan'],
        ];

        return $includeAll ? [['value' => '', 'label' => 'Semua status'], ...$options] : $options;
    }

    private function visibilityOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => FormVisibility::Public->value, 'label' => 'Terbuka'],
            ['value' => FormVisibility::MembersOnly->value, 'label' => 'Ahli sahaja'],
        ];

        return $includeAll ? [['value' => '', 'label' => 'Semua akses'], ...$options] : $options;
    }

    private function submissionMethodOptions(): array
    {
        return [
            ['value' => FormSubmissionMethod::OnlineOnly->value, 'label' => 'Hantar Online Sahaja'],
            ['value' => FormSubmissionMethod::RequiresStampedUpload->value, 'label' => 'Perlu Borang Bercop'],
        ];
    }

    private function fieldTypeOptions(): array
    {
        return collect(FormFieldType::cases())
            ->map(fn (FormFieldType $type) => ['value' => $type->value, 'label' => $this->fieldTypeLabel($type)])
            ->all();
    }

    private function fieldTypeConfigs(): array
    {
        $categories = [
            'maklumat_asas' => ['key' => 'maklumat_asas', 'label' => 'Maklumat Asas', 'icon' => 'Type'],
            'pilihan' => ['key' => 'pilihan', 'label' => 'Pilihan', 'icon' => 'ListChecks'],
            'maklumat_ahli' => ['key' => 'maklumat_ahli', 'label' => 'Maklumat Ahli (Auto Isi)', 'icon' => 'UserCheck'],
            'dokumen' => ['key' => 'dokumen', 'label' => 'Dokumen & Lampiran', 'icon' => 'Paperclip'],
            'kandungan' => ['key' => 'kandungan', 'label' => 'Kandungan Borang', 'icon' => 'FileText'],
        ];

        $fieldTypeConfigs = [
            // ── Maklumat Asas ──
            ['value' => 'short_text', 'label' => 'Nama / Teks', 'description' => 'Teks pendek satu baris. Sesuai untuk nama, jawapan ringkas.', 'category' => 'maklumat_asas', 'icon' => 'Type', 'keywords' => ['nama', 'teks', 'short'], 'isMemberAutofill' => false],
            ['value' => 'long_text', 'label' => 'Teks Panjang', 'description' => 'Untuk penerangan panjang, alamat atau catatan terperinci.', 'category' => 'maklumat_asas', 'icon' => 'AlignLeft', 'keywords' => ['teks panjang', 'penerangan'], 'isMemberAutofill' => false],
            ['value' => 'address_my', 'label' => 'Alamat', 'description' => 'Alamat lengkap Malaysia: alamat, poskod, bandar, negeri.', 'category' => 'maklumat_asas', 'icon' => 'MapPin', 'keywords' => ['alamat', 'rumah', 'surat-menyurat', 'address'], 'isMemberAutofill' => false, 'isAddress' => true],
            ['value' => 'email', 'label' => 'E-mel', 'description' => 'Untuk alamat e-mel dengan pengesahan format automatik.', 'category' => 'maklumat_asas', 'icon' => 'Mail', 'keywords' => ['emel', 'email'], 'isMemberAutofill' => false],
            ['value' => 'phone', 'label' => 'Telefon', 'description' => 'Nombor telefon termasuk kod negara.', 'category' => 'maklumat_asas', 'icon' => 'Phone', 'keywords' => ['tel', 'telefon'], 'isMemberAutofill' => false],
            ['value' => 'identity_no', 'label' => 'No. Kad Pengenalan', 'description' => 'No. Kad Pengenalan 12 digit.', 'category' => 'maklumat_asas', 'icon' => 'CreditCard', 'keywords' => ['ic', 'kad pengenalan', 'kp'], 'isMemberAutofill' => false],
            ['value' => 'number', 'label' => 'Nombor', 'description' => 'Untuk input nombor seperti bilangan, kuantiti.', 'category' => 'maklumat_asas', 'icon' => 'Hash', 'keywords' => ['nombor', 'kuantiti'], 'isMemberAutofill' => false],
            ['value' => 'currency', 'label' => 'Jumlah Wang (RM)', 'description' => 'Untuk jumlah wang dalam Ringgit Malaysia.', 'category' => 'maklumat_asas', 'icon' => 'DollarSign', 'keywords' => ['wang', 'rm', 'ringgit'], 'isMemberAutofill' => false],
            ['value' => 'date', 'label' => 'Tarikh', 'description' => 'Untuk memilih tarikh daripada kalendar.', 'category' => 'maklumat_asas', 'icon' => 'Calendar', 'keywords' => ['tarikh', 'kalendar'], 'isMemberAutofill' => false],

            // ── Pilihan ──
            ['value' => 'select', 'label' => 'Dropdown', 'description' => 'Senarai pilihan dalam menu jatuh.', 'category' => 'pilihan', 'icon' => 'ChevronDown', 'keywords' => ['dropdown', 'pilihan', 'senarai'], 'isMemberAutofill' => false, 'needsOptions' => true],
            ['value' => 'radio', 'label' => 'Radio', 'description' => 'Pilih satu pilihan daripada beberapa.', 'category' => 'pilihan', 'icon' => 'Circle', 'keywords' => ['radio', 'pilih satu'], 'isMemberAutofill' => false, 'needsOptions' => true],
            ['value' => 'checkbox', 'label' => 'Checkbox', 'description' => 'Pilih lebih daripada satu pilihan.', 'category' => 'pilihan', 'icon' => 'CheckSquare', 'keywords' => ['checkbox', 'pilih banyak'], 'isMemberAutofill' => false, 'needsOptions' => true],
            ['value' => 'yes_no', 'label' => 'Ya / Tidak', 'description' => 'Soalan ya atau tidak.', 'category' => 'pilihan', 'icon' => 'ToggleLeft', 'keywords' => ['ya', 'tidak'], 'isMemberAutofill' => false],

            // ── Maklumat Ahli (Auto Isi) ──
            ['value' => 'member_name', 'label' => 'Nama Ahli (Auto Isi)', 'description' => 'Nama penuh ahli akan diisi secara automatik.', 'category' => 'maklumat_ahli', 'icon' => 'User', 'keywords' => ['nama ahli', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_identity_no', 'label' => 'No. KP Ahli (Auto Isi)', 'description' => 'No. Kad Pengenalan ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Fingerprint', 'keywords' => ['ic ahli', 'kp ahli', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_address', 'label' => 'Alamat Ahli (Auto Isi)', 'description' => 'Alamat lengkap ahli akan diisi automatik daripada profil.', 'category' => 'maklumat_ahli', 'icon' => 'MapPin', 'keywords' => ['alamat ahli', 'auto isi', 'rumah'], 'isMemberAutofill' => true, 'isAddress' => true],
            ['value' => 'member_dob', 'label' => 'Tarikh Lahir (Auto Isi)', 'description' => 'Tarikh lahir ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'CalendarDays', 'keywords' => ['tarikh lahir', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_phone', 'label' => 'No. Telefon Ahli (Auto Isi)', 'description' => 'Nombor telefon ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Smartphone', 'keywords' => ['tel ahli', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_email', 'label' => 'E-mel Ahli (Auto Isi)', 'description' => 'Alamat e-mel ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Inbox', 'keywords' => ['emel ahli', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_member_no', 'label' => 'No. Ahli (Auto Isi)', 'description' => 'Nombor ahli akan diisi automatik daripada profil.', 'category' => 'maklumat_ahli', 'icon' => 'IdCard', 'keywords' => ['no ahli', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_position', 'label' => 'Jawatan (Auto Isi)', 'description' => 'Jawatan pekerjaan ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Briefcase', 'keywords' => ['jawatan', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_employer', 'label' => 'Majikan (Auto Isi)', 'description' => 'Nama majikan ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Building2', 'keywords' => ['majikan', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_employment_no', 'label' => 'No. Pekerja (Auto Isi)', 'description' => 'Nombor pekerja ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Badge', 'keywords' => ['no pekerja', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_bank', 'label' => 'Nama Bank (Auto Isi)', 'description' => 'Nama bank ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Landmark', 'keywords' => ['bank', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_bank_account', 'label' => 'No. Akaun Bank (Auto Isi)', 'description' => 'Nombor akaun bank ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'CreditCard', 'keywords' => ['akaun bank', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_marital_status', 'label' => 'Status Perkahwinan (Auto Isi)', 'description' => 'Status perkahwinan ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Heart', 'keywords' => ['status perkahwinan', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_department', 'label' => 'Jabatan (Auto Isi)', 'description' => 'Nama jabatan ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Building2', 'keywords' => ['jabatan', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_spouse_name', 'label' => 'Nama Pasangan (Auto Isi)', 'description' => 'Nama pasangan ahli akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Heart', 'keywords' => ['pasangan', 'auto isi'], 'isMemberAutofill' => true],
            ['value' => 'member_spouse_phone', 'label' => 'No. Telefon Pasangan (Auto Isi)', 'description' => 'Nombor telefon pasangan akan diisi automatik.', 'category' => 'maklumat_ahli', 'icon' => 'Phone', 'keywords' => ['telefon pasangan', 'auto isi'], 'isMemberAutofill' => true],

            // ── Dokumen & Lampiran ──
            ['value' => 'file', 'label' => 'Muat Naik Fail', 'description' => 'Pemohon boleh muat naik fail dokumen.', 'category' => 'dokumen', 'icon' => 'Upload', 'keywords' => ['muat naik', 'fail', 'dokumen'], 'isMemberAutofill' => false],
            ['value' => 'signature', 'label' => 'Tandatangan', 'description' => 'Pemohon boleh menandatangani secara digital.', 'category' => 'dokumen', 'icon' => 'Pen', 'keywords' => ['tandatangan', 'signature'], 'isMemberAutofill' => false],

            // ── Kandungan Borang ──
            ['value' => 'note', 'label' => 'Nota', 'description' => 'Nota teks ringkas untuk makluman.', 'category' => 'kandungan', 'icon' => 'StickyNote', 'keywords' => ['nota', 'peringatan'], 'isMemberAutofill' => false],
            ['value' => 'instruction_text', 'label' => 'Teks Arahan', 'description' => 'Teks arahan dengan latar biru.', 'category' => 'kandungan', 'icon' => 'Info', 'keywords' => ['arahan', 'panduan'], 'isMemberAutofill' => false],
            ['value' => 'agreement_checkbox', 'label' => 'Persetujuan', 'description' => 'Checkbox persetujuan terma dan syarat.', 'category' => 'kandungan', 'icon' => 'CheckSquare', 'keywords' => ['persetujuan', 'terma'], 'isMemberAutofill' => false],
            ['value' => 'office_use_box', 'label' => 'Kotak Kegunaan Pejabat', 'description' => 'Untuk diisi oleh pegawai koperasi.', 'category' => 'kandungan', 'icon' => 'FileText', 'keywords' => ['kegunaan pejabat', 'pegawai'], 'isMemberAutofill' => false],
        ];

        return compact('categories', 'fieldTypeConfigs');
    }

    private function fieldTypeLabel(FormFieldType $type): string
    {
        return match ($type) {
            FormFieldType::ShortText => 'Jawapan Pendek',
            FormFieldType::LongText => 'Jawapan Panjang',
            FormFieldType::Email => 'Email',
            FormFieldType::Phone => 'No. Telefon',
            FormFieldType::IdentityNo => 'No. Kad Pengenalan',
            FormFieldType::Number => 'Nombor',
            FormFieldType::Currency => 'Jumlah Wang (RM)',
            FormFieldType::Date => 'Tarikh',
            FormFieldType::Select => 'Dropdown',
            FormFieldType::Radio => 'Pilihan Tunggal',
            FormFieldType::Checkbox => 'Kotak Pilihan',
            FormFieldType::YesNo => 'Ya / Tidak',
            FormFieldType::File => 'Muat Naik Fail',
            FormFieldType::Signature => 'Tandatangan',
            FormFieldType::AgreementCheckbox => 'Persetujuan',
            FormFieldType::Note => 'Nota',
            FormFieldType::InstructionText => 'Teks Arahan',
            FormFieldType::OfficeUseBox => 'Kotak Kegunaan Pejabat',
            FormFieldType::AddressMy => 'Alamat',
            FormFieldType::MemberAddress => 'Alamat Ahli (Auto Isi)',
            FormFieldType::MemberName => 'Nama Ahli (Auto Isi)',
            FormFieldType::MemberIdentityNo => 'No. KP Ahli (Auto Isi)',
            FormFieldType::MemberDob => 'Tarikh Lahir (Auto Isi)',
            FormFieldType::MemberPhone => 'No. Telefon Ahli (Auto Isi)',
            FormFieldType::MemberEmail => 'E-mel Ahli (Auto Isi)',
            FormFieldType::MemberNo => 'No. Ahli (Auto Isi)',
            FormFieldType::MemberPosition => 'Jawatan (Auto Isi)',
            FormFieldType::MemberEmployer => 'Majikan (Auto Isi)',
            FormFieldType::MemberEmploymentNo => 'No. Pekerja (Auto Isi)',
            FormFieldType::MemberBank => 'Nama Bank (Auto Isi)',
            FormFieldType::MemberBankAccount => 'No. Akaun Bank (Auto Isi)',
            FormFieldType::MemberMaritalStatus => 'Status Perkahwinan (Auto Isi)',
            FormFieldType::MemberDepartment => 'Jabatan (Auto Isi)',
            FormFieldType::MemberSpouseName => 'Nama Pasangan (Auto Isi)',
            FormFieldType::MemberSpousePhone => 'No. Telefon Pasangan (Auto Isi)',
        };
    }
}