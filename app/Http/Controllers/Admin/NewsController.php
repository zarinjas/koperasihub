<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NewsCategory;
use App\Enums\NewsStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNewsRequest;
use App\Http\Requests\Admin\UpdateNewsRequest;
use App\Models\Cooperative;
use App\Models\News;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();

        $news = News::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, NewsStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($category, NewsCategory::values(), true), fn ($query) => $query->where('category', $category))
            ->ordered()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (News $item) => $this->serializeNews($item));

        return Inertia::render('Admin/Pages/News/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
            ],
            'news' => $news,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'categoryOptions' => $this->categoryOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_NEWS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_NEWS) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_NEWS) ?? false,
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_NEWS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/News/Form', [
            'mode' => 'create',
            'newsRecord' => null,
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function edit(News $news): Response
    {
        $this->ensureSameCooperative($news);

        return Inertia::render('Admin/Pages/News/Form', [
            'mode' => 'edit',
            'newsRecord' => $this->serializeNews($news),
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreNewsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'cooperative_id' => $this->activeCooperative()?->id,
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? Str::slug($validated['title']),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === NewsStatus::Published->value
                ? ($validated['published_at'] ?? now())
                : ($validated['published_at'] ?? null),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        $news = News::query()->create($data);

        $this->auditLogs->record('news.created', $news, [], [
            'title' => $news->title,
            'status' => $news->status->value,
        ]);

        return redirect()
            ->route('admin.news.edit', $news)
            ->with('status', 'Berita berjaya dicipta.');
    }

    public function update(UpdateNewsRequest $request, News $news): RedirectResponse
    {
        $this->ensureSameCooperative($news);
        $validated = $request->validated();
        $oldValues = ['status' => $news->status->value, 'title' => $news->title];

        $data = [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? Str::slug($validated['title']),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === NewsStatus::Published->value
                ? ($validated['published_at'] ?? $news->published_at ?? now())
                : ($validated['published_at'] ?? null),
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            if ($news->image_path) {
                Storage::disk('public')->delete($news->image_path);
            }

            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        $news->update($data);

        $this->auditLogs->record('news.updated', $news, $oldValues, [
            'title' => $news->title,
            'status' => $news->status->value,
        ]);

        return back()->with('status', 'Berita berjaya dikemas kini.');
    }

    public function publish(News $news): RedirectResponse
    {
        $this->ensureSameCooperative($news);
        $oldValues = ['status' => $news->status->value];

        $news->update([
            'status' => NewsStatus::Published->value,
            'published_at' => $news->published_at ?? now(),
        ]);

        $this->auditLogs->record('news.published', $news, $oldValues, [
            'status' => $news->status->value,
            'published_at' => $news->published_at?->toISOString(),
        ]);

        return back()->with('status', 'Berita berjaya diterbitkan.');
    }

    public function unpublish(News $news): RedirectResponse
    {
        return $this->updateStatus($news, NewsStatus::Draft, 'Berita dikembalikan ke draf.');
    }

    public function archive(News $news): RedirectResponse
    {
        return $this->updateStatus($news, NewsStatus::Archived, 'Berita berjaya diarkibkan.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $this->ensureSameCooperative($news);
        $oldValues = ['title' => $news->title, 'status' => $news->status->value];

        if ($news->image_path) {
            Storage::disk('public')->delete($news->image_path);
        }

        $news->delete();

        $this->auditLogs->record('news.deleted', $news, $oldValues, [
            'deleted_at' => $news->deleted_at?->toISOString(),
        ]);

        return redirect()
            ->route('admin.news.index')
            ->with('status', 'Berita berjaya dipadam.');
    }

    private function updateStatus(News $news, NewsStatus $status, string $message): RedirectResponse
    {
        $this->ensureSameCooperative($news);
        $oldValues = ['status' => $news->status->value];

        $news->update(['status' => $status->value]);

        $this->auditLogs->record(
            match ($status) {
                NewsStatus::Published => 'news.published',
                NewsStatus::Archived => 'news.archived',
                NewsStatus::Draft => 'news.unpublished',
            },
            $news,
            $oldValues,
            ['status' => $news->status->value],
        );

        return back()->with('status', $message);
    }

    private function serializeNews(News $news): array
    {
        return [
            'id' => $news->id,
            'title' => $news->title,
            'slug' => $news->slug,
            'excerpt' => $news->excerpt,
            'content' => $news->content,
            'image_path' => $news->image_path,
            'image_url' => $news->imageUrl(),
            'category' => $news->category,
            'status' => $news->status->value,
            'published_at' => $news->published_at?->format('Y-m-d\TH:i'),
            'published_at_human' => $news->published_at?->format('d/m/Y'),
            'updated_at' => $news->updated_at?->format('d/m/Y H:i'),
            'public_url' => route('public.news.show', $news->slug),
            'content_preview' => Str::limit(strip_tags((string) $news->content), 140),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(News $news): void
    {
        abort_unless($news->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => NewsStatus::Draft->value, 'label' => 'Draf'],
            ['value' => NewsStatus::Published->value, 'label' => 'Diterbitkan'],
            ['value' => NewsStatus::Archived->value, 'label' => 'Diarkibkan'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => NewsCategory::General->value, 'label' => 'Umum'],
            ['value' => NewsCategory::Announcement->value, 'label' => 'Pengumuman'],
            ['value' => NewsCategory::Event->value, 'label' => 'Acara'],
            ['value' => NewsCategory::Achievement->value, 'label' => 'Pencapaian'],
            ['value' => NewsCategory::Community->value, 'label' => 'Komuniti'],
            ['value' => NewsCategory::Education->value, 'label' => 'Pendidikan'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua kategori'], ...$options]
            : $options;
    }
}