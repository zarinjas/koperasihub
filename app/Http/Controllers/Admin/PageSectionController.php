<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageSectionRequest;
use App\Http\Requests\Admin\UpdatePageSectionRequest;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageSectionController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(Page $page): JsonResponse
    {
        $this->ensureSameCooperative($page);

        return response()->json([
            'sections' => $page->sections()->get(),
        ]);
    }

    public function store(StorePageSectionRequest $request, Page $page): JsonResponse
    {
        $this->ensureSameCooperative($page);

        $section = $page->sections()->create([
            ...$request->validated(),
            'cooperative_id' => $page->cooperative_id,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'sort_order' => $request->validated('sort_order') ?? (($page->sections()->max('sort_order') ?? 0) + 1),
            'is_active' => $request->validated('is_active', true),
        ]);

        return response()->json([
            'message' => 'Seksyen halaman berjaya ditambah.',
            'section' => $section,
        ], 201);
    }

    public function update(UpdatePageSectionRequest $request, PageSection $pageSection): JsonResponse
    {
        $this->ensureSameCooperative($pageSection->page);

        $pageSection->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Seksyen halaman berjaya dikemas kini.',
            'section' => $pageSection->fresh(),
        ]);
    }

    public function destroy(PageSection $pageSection): JsonResponse
    {
        $this->ensureSameCooperative($pageSection->page);

        $pageSection->delete();

        return response()->json([
            'message' => 'Seksyen halaman berjaya dipadam.',
        ]);
    }

    public function reorder(Request $request, Page $page): JsonResponse
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

        return response()->json([
            'message' => 'Susunan seksyen berjaya dikemas kini.',
        ]);
    }

    private function ensureSameCooperative(Page $page): void
    {
        $cooperativeId = $this->settings->activeCooperative()?->id;

        abort_unless($cooperativeId && $page->cooperative_id === $cooperativeId, 404);
    }
}
