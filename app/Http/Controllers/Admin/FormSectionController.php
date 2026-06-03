<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFormSectionFromTemplateRequest;
use App\Http\Requests\Admin\StoreFormSectionRequest;
use App\Http\Requests\Admin\StoreFormSectionTemplateRequest;
use App\Http\Requests\Admin\UpdateFormSectionRequest;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\OnlineForm;
use App\Services\AuditLogService;
use App\Services\Forms\FormSectionTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormSectionController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly FormSectionTemplateService $templates,
    ) {}

    public function index(OnlineForm $onlineForm, Request $request): Response
    {
        $this->ensureSameCooperative($onlineForm);

        return Inertia::render('Admin/Pages/Forms/Sections/Index', [
            'formRecord' => [
                'id' => $onlineForm->id,
                'title' => $onlineForm->title,
                'status' => $onlineForm->status->value,
                'fields_url' => route('admin.forms.fields.index', $onlineForm),
                'preview_pdf_url' => route('admin.forms.preview-pdf', $onlineForm),
            ],
            'sections' => $onlineForm->sections()
                ->withCount('fields')
                ->get()
                ->map(fn (FormSection $section) => [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'page_break_before' => $section->page_break_before,
                    'is_active' => $section->is_active,
                    'fields_count' => $section->fields_count,
                ])->all(),
            'sectionTemplates' => $this->templates->availableTemplates($this->activeCooperative()?->id),
            'editingSectionId' => $request->integer('edit') ?: null,
        ]);
    }

    public function store(StoreFormSectionRequest $request, OnlineForm $onlineForm): RedirectResponse|JsonResponse
    {
        $this->ensureSameCooperative($onlineForm);

        $section = $onlineForm->sections()->create([
            ...$request->validated(),
        ]);

        $this->auditLog->record('form_section.created', $onlineForm, newValues: $section->toArray());

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'section' => $this->serializeSection($section)]);
        }

        return back()->with('status', 'Seksyen borang berjaya ditambah.');
    }

    public function storeFromTemplate(StoreFormSectionFromTemplateRequest $request, OnlineForm $onlineForm): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);

        $section = $this->templates->createSectionFromTemplate($onlineForm, $request->validated('template_ref'));

        $this->auditLog->record('form_section.created_from_template', $onlineForm, newValues: $section->toArray());

        return back()->with('status', 'Seksyen daripada template berjaya ditambah.');
    }

    public function update(UpdateFormSectionRequest $request, OnlineForm $onlineForm, FormSection $section): RedirectResponse|JsonResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($section->online_form_id === $onlineForm->id, 404);

        $old = $section->toArray();
        $section->update($request->validated());

        $this->auditLog->record('form_section.updated', $onlineForm, $old, $section->fresh()->toArray());

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'section' => $this->serializeSection($section->fresh())]);
        }

        return back()->with('status', 'Seksyen borang berjaya dikemas kini.');
    }

    public function saveAsTemplate(StoreFormSectionTemplateRequest $request, OnlineForm $onlineForm, FormSection $section): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($section->online_form_id === $onlineForm->id, 404);

        $template = $this->templates->saveSectionAsTemplate($section->load('form'), $request->user());

        $this->auditLog->record('form_section.template_saved', $onlineForm, newValues: $template->toArray());

        return back()->with('status', 'Template seksyen berjaya disimpan.');
    }

    public function destroy(OnlineForm $onlineForm, FormSection $section): RedirectResponse|JsonResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($section->online_form_id === $onlineForm->id, 404);

        $this->auditLog->record('form_section.deleted', $onlineForm, $section->toArray());
        $section->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Seksyen borang berjaya dipadam.');
    }

    private function serializeSection(FormSection $section): array
    {
        return [
            'id' => $section->id,
            'online_form_id' => $section->online_form_id,
            'title' => $section->title,
            'description' => $section->description,
            'page_break_before' => $section->page_break_before,
            'is_active' => $section->is_active,
            'sort_order' => $section->sort_order,
            'fields' => $section->fields->map(fn (FormField $field) => [
                'id' => $field->id,
                'form_section_id' => $field->form_section_id,
                'label' => $field->label,
                'field_key' => $field->field_key,
                'type' => $field->type->value,
                'type_label' => $field->type->value,
                'placeholder' => $field->placeholder,
                'help_text' => $field->help_text,
                'is_required' => $field->is_required,
                'options_json' => $field->options_json ?? [],
                'options_text' => implode("\n", $field->options_json ?? []),
                'validation_json' => $field->validation_json ?? [],
                'settings_json' => $field->settings_json ?? [],
                'display_mode' => $field->displayMode()->value,
                'is_active' => $field->is_active,
                'sort_order' => $field->sort_order,
            ])->all(),
        ];
    }
}