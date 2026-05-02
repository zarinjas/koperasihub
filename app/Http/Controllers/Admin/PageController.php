<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageSectionType;
use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Cooperative;
use App\Models\Page;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function index(): Response
    {
        $cooperative = $this->activeCooperative();
        $pages = collect();

        if ($cooperative) {
            $pages = Page::query()
                ->where('cooperative_id', $cooperative->id)
                ->withCount('sections')
                ->latest('updated_at')
                ->get()
                ->map(fn (Page $page) => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'template' => $page->template->value,
                    'status' => $page->status->value,
                    'sections_count' => $page->sections_count,
                    'published_at' => $page->published_at?->toIso8601String(),
                    'updated_at' => $page->updated_at?->toIso8601String(),
                ]);
        }

        return Inertia::render('Admin/Pages/Placeholder', [
            'title' => 'Halaman CMS',
            'description' => 'Asas backend CMS telah disediakan. Editor visual akan dibina dalam fasa seterusnya.',
            'pages' => $pages,
            'sectionTypes' => PageSectionType::values(),
            'statuses' => PageStatus::values(),
            'templates' => PageTemplate::values(),
            'canEdit' => request()->user()?->can(AccessControl::PERMISSION_EDIT_PAGES) ?? false,
        ]);
    }

    public function show(Page $page): JsonResponse
    {
        $this->ensureSameCooperative($page);

        return response()->json([
            'page' => $page->load(['sections' => fn ($query) => $query->orderBy('sort_order')]),
        ]);
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        $cooperative = $this->activeCooperative();

        $page = Page::query()->create([
            ...$request->validated(),
            'cooperative_id' => $cooperative->id,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Halaman berjaya dicipta.',
            'page' => $page,
        ], 201);
    }

    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $this->ensureSameCooperative($page);

        $page->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Halaman berjaya dikemas kini.',
            'page' => $page->fresh(),
        ]);
    }

    public function publish(Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);

        $page->update([
            'status' => PageStatus::Published,
            'published_at' => $page->published_at ?? now(),
            'updated_by' => request()->user()?->id,
        ]);

        return back()->with('status', 'Halaman berjaya diterbitkan.');
    }

    public function archive(Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);

        $page->update([
            'status' => PageStatus::Archived,
            'updated_by' => request()->user()?->id,
        ]);

        return back()->with('status', 'Halaman berjaya diarkibkan.');
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative()
            ?? Cooperative::query()->first();
    }

    private function ensureSameCooperative(Page $page): void
    {
        $cooperative = $this->activeCooperative();

        abort_unless($cooperative && $page->cooperative_id === $cooperative->id, 404);
    }
}
