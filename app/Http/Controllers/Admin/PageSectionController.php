<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageSectionRequest;
use App\Http\Requests\Admin\UpdatePageSectionRequest;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\Settings\SettingsService;
use App\Support\CmsSectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageSectionController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly CmsSectionRegistry $sections,
    ) {}

    public function index(Request $request, Page $page): Response
    {
        $this->ensureSameCooperative($page);

        $page->load(['sections' => fn ($query) => $query->orderBy('sort_order')])->loadCount('sections');

        return Inertia::render('Admin/Pages/Cms/Sections/Index', [
            'pageRecord' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => $page->status->value,
                'template' => $page->template->value,
                'sections_count' => $page->sections_count,
            ],
            'sections' => $page->sections->map(fn (PageSection $section) => $this->serializeSection($section))->values()->all(),
            'sectionDefinitions' => $this->sections->frontendDefinitions(),
            'selectedSectionId' => $request->integer('section'),
        ]);
    }

    public function store(StorePageSectionRequest $request, Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);

        $validated = $request->validated();
        $type = $request->string('type')->toString();
        $merged = $this->sections->mergeWithDefaults($type, data_get($validated, 'data', []), data_get($validated, 'settings', []));

        $section = $page->sections()->create([
            'type' => $type,
            'name' => data_get($validated, 'name') ?: $this->sections->frontendDefinition($type)['name_default'],
            'data' => $merged['data'],
            'settings' => $merged['settings'],
            'cooperative_id' => $page->cooperative_id,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'sort_order' => data_get($validated, 'sort_order', ($page->sections()->max('sort_order') ?? 0) + 1),
            'is_active' => data_get($validated, 'is_active', true),
        ]);

        return redirect()
            ->route('admin.pages.sections.index', ['page' => $page, 'section' => $section->id])
            ->with('status', 'Seksyen halaman berjaya ditambah.');
    }

    public function update(UpdatePageSectionRequest $request, PageSection $pageSection): RedirectResponse
    {
        $this->ensureSameCooperative($pageSection->page);

        $validated = $request->validated();
        $type = $request->string('type')->toString();
        $merged = $this->sections->mergeWithDefaults($type, data_get($validated, 'data', []), data_get($validated, 'settings', []));

        $pageSection->update([
            'type' => $type,
            'name' => data_get($validated, 'name'),
            'data' => $merged['data'],
            'settings' => $merged['settings'],
            'sort_order' => data_get($validated, 'sort_order', $pageSection->sort_order),
            'is_active' => data_get($validated, 'is_active', $pageSection->is_active),
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'Seksyen halaman berjaya dikemas kini.');
    }

    public function destroy(PageSection $pageSection): RedirectResponse
    {
        $this->ensureSameCooperative($pageSection->page);

        $pageSection->delete();

        return back()->with('status', 'Seksyen halaman berjaya dipadam.');
    }

    public function reorder(Request $request, Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);

        $validated = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'integer'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0'],
        ], [
            'sections.required' => 'Senarai seksyen diperlukan.',
        ]);

        $sectionIds = collect($validated['sections'])->pluck('id')->all();

        abort_unless(
            $page->sections()->whereIn('id', $sectionIds)->count() === count($sectionIds),
            422,
            'Seksyen yang dihantar tidak sah.'
        );

        foreach ($validated['sections'] as $section) {
            $page->sections()
                ->whereKey($section['id'])
                ->update([
                    'sort_order' => $section['sort_order'],
                    'updated_by' => $request->user()?->id,
                ]);
        }

        return back()->with('status', 'Susunan seksyen berjaya dikemas kini.');
    }

    private function serializeSection(PageSection $section): array
    {
        $definition = $this->sections->frontendDefinition($section->type->value);

        return [
            'id' => $section->id,
            'type' => $section->type->value,
            'type_label' => $definition['label'],
            'name' => $section->name,
            'sort_order' => $section->sort_order,
            'is_active' => $section->is_active,
            'data' => $section->data ?? [],
            'settings' => $section->settings ?? [],
            'unknown_data_keys' => $this->sections->unknownKeys($section->type->value, $section->data ?? [], 'data'),
            'unknown_settings_keys' => $this->sections->unknownKeys($section->type->value, $section->settings ?? [], 'settings'),
            'updated_at' => $section->updated_at?->format('d/m/Y H:i'),
        ];
    }

    private function ensureSameCooperative(Page $page): void
    {
        $cooperativeId = $this->settings->activeCooperative()?->id;

        abort_unless($cooperativeId && $page->cooperative_id === $cooperativeId, 404);
    }
}
