<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FormFieldDisplayMode;
use App\Enums\FormFieldType;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFormFieldRequest;
use App\Http\Requests\Admin\UpdateFormFieldRequest;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\OnlineForm;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FormFieldController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(OnlineForm $onlineForm, Request $request): Response
    {
        $this->ensureSameCooperative($onlineForm);

        return Inertia::render('Admin/Pages/Forms/Fields/Index', [
            'formRecord' => [
                'id' => $onlineForm->id,
                'title' => $onlineForm->title,
                'status' => $onlineForm->status->value,
                'sections_url' => route('admin.forms.sections.index', $onlineForm),
                'preview_pdf_url' => route('admin.forms.preview-pdf', $onlineForm),
                'submissions_url' => route('admin.forms.submissions.index', $onlineForm),
            ],
            'sections' => $onlineForm->sections()
                ->with(['fields' => fn ($query) => $query->latest()->orderBy('id')])
                ->get()
                ->map(fn (FormSection $section) => [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'page_break_before' => $section->page_break_before,
                    'is_active' => $section->is_active,
                    'fields' => $section->fields->map(fn (FormField $field) => $this->serializeField($field))->all(),
                ])->all(),
            'sectionOptions' => $onlineForm->sections()
                ->get()
                ->map(fn (FormSection $section) => ['value' => $section->id, 'label' => $section->title])
                ->all(),
            'fieldTypeOptions' => collect(FormFieldType::cases())
                ->map(fn (FormFieldType $type) => ['value' => $type->value, 'label' => $this->fieldTypeLabel($type)])
                ->all(),
            'displayModeOptions' => [
                ['value' => FormFieldDisplayMode::OnlineAndPrint->value, 'label' => 'Online dan Cetakan'],
                ['value' => FormFieldDisplayMode::OnlineOnly->value, 'label' => 'Online sahaja'],
                ['value' => FormFieldDisplayMode::PrintOnly->value, 'label' => 'Cetakan sahaja'],
            ],
            'editingFieldId' => $request->integer('edit') ?: null,
        ]);
    }

    public function store(StoreFormFieldRequest $request, OnlineForm $onlineForm): RedirectResponse|JsonResponse
    {
        $this->ensureSameCooperative($onlineForm);

        $section = $onlineForm->sections()->findOrFail($request->integer('form_section_id'));
        $payload = $this->validatedPayload($request, $onlineForm);
        $payload['field_key'] = $this->ensureUniqueFieldKey($onlineForm->id, $payload['field_key'] ?: $payload['label'], null);
        $field = $section->fields()->create($payload);

        $this->auditLog->record('form_field.created', $onlineForm, newValues: $field->toArray());

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'field' => $this->serializeField($field)]);
        }

        return back()->with('status', 'Field borang berjaya ditambah.');
    }

    public function update(UpdateFormFieldRequest $request, OnlineForm $onlineForm, FormField $field): RedirectResponse|JsonResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($field->online_form_id === $onlineForm->id, 404);

        $old = $field->toArray();
        $payload = $this->validatedPayload($request, $onlineForm, $field);
        $payload['field_key'] = $this->ensureUniqueFieldKey($onlineForm->id, $payload['field_key'] ?: $payload['label'], $field->id);
        $field->update($payload);

        $this->auditLog->record('form_field.updated', $onlineForm, $old, $field->fresh()->toArray());

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'field' => $this->serializeField($field->fresh())]);
        }

        return back()->with('status', 'Field borang berjaya dikemas kini.');
    }

    public function destroy(OnlineForm $onlineForm, FormField $field): RedirectResponse|JsonResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($field->online_form_id === $onlineForm->id, 404);

        $this->auditLog->record('form_field.deleted', $onlineForm, $field->toArray());
        $field->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Field borang berjaya dipadam.');
    }

    private function ensureUniqueFieldKey(int $formId, string $key, ?int $ignoreId): string
    {
        $baseKey = $key ? Str::slug($key, '_') : 'field_' . Str::random(6);
        $baseKey = preg_replace('/[^a-z0-9_]/', '', strtolower($baseKey));
        $baseKey = $baseKey ?: 'field_' . Str::random(6);

        $existing = FormField::query()
            ->where('online_form_id', $formId)
            ->where('field_key', $baseKey)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($existing) {
            $counter = 1;
            while (FormField::query()
                ->where('online_form_id', $formId)
                ->where('field_key', $baseKey . '_' . $counter)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
            ) { $counter++; }
            $baseKey .= '_' . $counter;
        }

        return $baseKey;
    }

    private function validatedPayload(Request $request, OnlineForm $onlineForm, ?FormField $field = null): array
    {
        $validated = $request->validated();
        $sectionId = (int) $validated['form_section_id'];
        $section = $onlineForm->sections()->findOrFail($sectionId);

        return [
            'online_form_id' => $onlineForm->id,
            'form_section_id' => $section->id,
            'label' => $validated['label'],
            'field_key' => data_get($validated, 'field_key') ?: $validated['label'],
            'type' => $validated['type'],
            'placeholder' => $validated['placeholder'] ?? null,
            'help_text' => $validated['help_text'] ?? null,
            'is_required' => (bool) $validated['is_required'],
            'options_json' => $this->parseOptions($validated['options_text'] ?? ''),
            'validation_json' => $validated['validation_json'] ?? [],
            'settings_json' => $this->normalizedSettings($validated['settings_json'] ?? []),
            'is_active' => (bool) $validated['is_active'],
        ];
    }

    private function parseOptions(string $options): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $options) ?: [])
            ->map(fn (string $option) => trim($option))
            ->filter()
            ->values()
            ->all();
    }

    private function serializeField(FormField $field): array
    {
        return [
            'id' => $field->id,
            'form_section_id' => $field->form_section_id,
            'label' => $field->label,
            'field_key' => $field->field_key,
            'type' => $field->type->value,
            'type_label' => $this->fieldTypeLabel($field->type),
            'placeholder' => $field->placeholder,
            'help_text' => $field->help_text,
            'is_required' => $field->is_required,
            'options_json' => $field->options_json ?? [],
            'options_text' => implode("\n", $field->options_json ?? []),
            'validation_json' => $field->validation_json ?? [],
            'settings_json' => $field->settings_json ?? [],
            'display_mode' => $field->displayMode()->value,
            'is_active' => $field->is_active,
        ];
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

    private function normalizedSettings(array $settings): array
    {
        $displayMode = $settings['display_mode'] ?? FormFieldDisplayMode::OnlineAndPrint->value;

        if (! in_array($displayMode, FormFieldDisplayMode::values(), true)) {
            $displayMode = FormFieldDisplayMode::OnlineAndPrint->value;
        }

        return [
            ...$settings,
            'display_mode' => $displayMode,
            'print_only' => $displayMode === FormFieldDisplayMode::PrintOnly->value,
        ];
    }

}