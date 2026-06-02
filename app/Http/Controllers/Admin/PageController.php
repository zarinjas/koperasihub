<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Cooperative;
use App\Models\Page;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): Response
    {
        $cooperative = $this->activeCooperative();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        $pages = Page::query()
            ->when($cooperative, fn ($query) => $query->where('cooperative_id', $cooperative->id))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('meta_title', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, PageStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->withCount('sections')
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Page $page) => $this->serializePage($page));

        return Inertia::render('Admin/Pages/Cms/Pages/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'pages' => $pages,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_PAGES) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_PAGES) ?? false,
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_PAGES) ?? false,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Pages/Cms/Pages/Form', [
            'mode' => 'create',
            'pageRecord' => null,
            'templateOptions' => $this->templateOptions(),
            'statusOptions' => $this->statusOptions(),
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_PAGES) ?? false,
        ]);
    }

    public function edit(Request $request, Page $page): Response
    {
        $this->ensureSameCooperative($page);

        return Inertia::render('Admin/Pages/Cms/Pages/Form', [
            'mode' => 'edit',
            'pageRecord' => $this->serializePage($page->loadCount('sections')),
            'templateOptions' => $this->templateOptions(),
            'statusOptions' => $this->statusOptions(),
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_PAGES) ?? false,
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $cooperative = $this->activeCooperative();

        abort_unless($cooperative, 422, 'Koperasi aktif tidak ditemui.');

        $data = $request->validated();
        unset($data['featured_image']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image_path'] = $request->file('featured_image')->store('pages', 'public');
        }

        $data['cooperative_id'] = $cooperative->id;
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $page = Page::query()->create($data);
        $this->auditLogs->record('page_created', $page, [], $this->pageAuditSnapshot($page));

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'Halaman berjaya dicipta.');
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);
        $oldValues = $this->pageAuditSnapshot($page);

        $data = $request->validated();
        unset($data['featured_image']);

        if ($request->hasFile('featured_image')) {
            if ($page->featured_image_path) {
                Storage::disk('public')->delete($page->featured_image_path);
            }

            $data['featured_image_path'] = $request->file('featured_image')->store('pages', 'public');
        }

        $data['updated_by'] = $request->user()?->id;

        $page->update($data);
        $this->auditLogs->record('page_updated', $page, $oldValues, $this->pageAuditSnapshot($page));

        return back()->with('status', 'Halaman berjaya dikemas kini.');
    }

    public function publish(Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);
        $oldValues = $this->pageAuditSnapshot($page);

        $page->update([
            'status' => PageStatus::Published,
            'published_at' => $page->published_at ?? now(),
            'updated_by' => request()->user()?->id,
        ]);
        $this->auditLogs->record('page_published', $page, $oldValues, $this->pageAuditSnapshot($page));

        return back()->with('status', 'Halaman berjaya diterbitkan.');
    }

    public function unpublish(Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);
        $oldValues = $this->pageAuditSnapshot($page);

        $page->update([
            'status' => PageStatus::Draft,
            'updated_by' => request()->user()?->id,
        ]);
        $this->auditLogs->record('page_unpublished', $page, $oldValues, $this->pageAuditSnapshot($page));

        return back()->with('status', 'Halaman berjaya dinyahterbit.');
    }

    public function archive(Page $page): RedirectResponse
    {
        $this->ensureSameCooperative($page);
        $oldValues = $this->pageAuditSnapshot($page);

        $page->update([
            'status' => PageStatus::Archived,
            'updated_by' => request()->user()?->id,
        ]);
        $this->auditLogs->record('page_archived', $page, $oldValues, $this->pageAuditSnapshot($page));

        return back()->with('status', 'Halaman berjaya diarkibkan.');
    }

    private function pageAuditSnapshot(Page $page): array
    {
        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'template' => $page->template->value,
            'status' => $page->status->value,
            'published_at' => $page->published_at?->toISOString(),
        ];
    }

    private function serializePage(Page $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'template' => $page->template->value,
            'summary' => $page->summary,
            'status' => $page->status->value,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'featured_image_path' => $page->featured_image_path,
            'featured_image_url' => $page->featuredImageUrl(),
            'sections_count' => $page->sections_count,
            'published_at' => $page->published_at?->format('Y-m-d\TH:i'),
            'published_at_human' => $page->published_at?->format('d/m/Y H:i'),
            'updated_at' => $page->updated_at?->format('d/m/Y H:i'),
        ];
    }

    private function templateOptions(): array
    {
        return [
            ['value' => PageTemplate::Default->value, 'label' => 'Default'],
            ['value' => PageTemplate::Homepage->value, 'label' => 'Homepage'],
            ['value' => PageTemplate::Landing->value, 'label' => 'Landing'],
            ['value' => PageTemplate::Service->value, 'label' => 'Service'],
            ['value' => PageTemplate::Contact->value, 'label' => 'Contact'],
        ];
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => PageStatus::Draft->value, 'label' => 'Draf'],
            ['value' => PageStatus::Published->value, 'label' => 'Diterbitkan'],
            ['value' => PageStatus::Archived->value, 'label' => 'Diarkibkan'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Page $page): void
    {
        $cooperative = $this->activeCooperative();

        abort_unless($cooperative && $page->cooperative_id === $cooperative->id, 404);
    }
}