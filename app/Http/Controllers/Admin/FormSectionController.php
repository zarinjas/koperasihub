<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFormSectionFromTemplateRequest;
use App\Http\Requests\Admin\StoreFormSectionRequest;
use App\Http\Requests\Admin\StoreFormSectionTemplateRequest;
use App\Http\Requests\Admin\UpdateFormSectionRequest;
use App\Models\FormSection;
use App\Models\OnlineForm;
use App\Services\AuditLogService;
use App\Services\Forms\FormSectionTemplateService;
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
                    'sort_order' => $section->sort_order,
                    'is_active' => $section->is_active,
                    'fields_count' => $section->fields_count,
                ])->all(),
            'sectionTemplates' => $this->templates->availableTemplates($this->activeCooperative()?->id),
            'editingSectionId' => $request->integer('edit') ?: null,
        ]);
    }

    public function store(StoreFormSectionRequest $request, OnlineForm $onlineForm): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);

        $section = $onlineForm->sections()->create([
            ...$request->validated(),
            'sort_order' => $request->validated('sort_order') ?? ((int) $onlineForm->sections()->max('sort_order') + 1),
        ]);

        $this->auditLog->record('form_section.created', $onlineForm, newValues: $section->toArray());

        return back()->with('status', 'Seksyen borang berjaya ditambah.');
    }

    public function storeFromTemplate(StoreFormSectionFromTemplateRequest $request, OnlineForm $onlineForm): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);

        $section = $this->templates->createSectionFromTemplate($onlineForm, $request->validated('template_ref'));

        $this->auditLog->record('form_section.created_from_template', $onlineForm, newValues: $section->toArray());

        return back()->with('status', 'Seksyen daripada template berjaya ditambah.');
    }

    public function update(UpdateFormSectionRequest $request, OnlineForm $onlineForm, FormSection $section): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($section->online_form_id === $onlineForm->id, 404);

        $old = $section->toArray();
        $section->update($request->validated());

        $this->auditLog->record('form_section.updated', $onlineForm, $old, $section->fresh()->toArray());

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

    public function moveUp(OnlineForm $onlineForm, FormSection $section): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($section->online_form_id === $onlineForm->id, 404);

        $swap = $onlineForm->sections()
            ->where('sort_order', '<', $section->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($swap) {
            $this->swapSortOrder($section, $swap);
        }

        return back()->with('status', 'Susunan seksyen dikemas kini.');
    }

    public function moveDown(OnlineForm $onlineForm, FormSection $section): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($section->online_form_id === $onlineForm->id, 404);

        $swap = $onlineForm->sections()
            ->where('sort_order', '>', $section->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($swap) {
            $this->swapSortOrder($section, $swap);
        }

        return back()->with('status', 'Susunan seksyen dikemas kini.');
    }

    public function destroy(OnlineForm $onlineForm, FormSection $section): RedirectResponse
    {
        $this->ensureSameCooperative($onlineForm);
        abort_unless($section->online_form_id === $onlineForm->id, 404);

        $this->auditLog->record('form_section.deleted', $onlineForm, $section->toArray());
        $section->delete();

        return back()->with('status', 'Seksyen borang berjaya dipadam.');
    }

    private function swapSortOrder(FormSection $first, FormSection $second): void
    {
        $original = $first->sort_order;
        $first->update(['sort_order' => $second->sort_order]);
        $second->update(['sort_order' => $original]);
    }
}