<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class NewsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        if (! Schema::hasTable('news')) {
            return Inertia::render('Public/Pages/News/Index', [
                'news' => ['data' => [], 'links' => []],
                'filters' => ['search' => '', 'category' => ''],
                'categoryOptions' => [],
            ]);
        }

        $search = trim((string) $request->string('search'));
        $category = $request->string('category')->toString();

        $news = News::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->published()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->when(filled($category), fn ($query) => $query->where('category', $category))
            ->ordered()
            ->paginate(9)
            ->withQueryString()
            ->through(fn (News $item) => $this->serializeForListing($item));

        return Inertia::render('Public/Pages/News/Index', [
            'news' => $news,
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function show(Request $request, string $slug): SymfonyResponse
    {
        if (! Schema::hasTable('news')) {
            abort(404);
        }

        $news = News::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->forPublicSlug($slug)
            ->first();

        if (! $news) {
            return Inertia::render('Public/Pages/NotFound', [
                'requestedPath' => $request->path(),
            ])->toResponse($request)->setStatusCode(404);
        }

        $suggested = News::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->published()
            ->where('id', '!=', $news->id)
            ->where(function ($query) use ($news): void {
                if ($news->category) {
                    $query->where('category', $news->category);
                }
            })
            ->ordered()
            ->limit(3)
            ->get();

        if ($suggested->count() < 3) {
            $needed = 3 - $suggested->count();
            $existingIds = $suggested->pluck('id')->push($news->id)->all();

            $more = News::query()
                ->where('cooperative_id', $this->settings->activeCooperative()?->id)
                ->published()
                ->whereNotIn('id', $existingIds)
                ->ordered()
                ->limit($needed)
                ->get();

            $suggested = $suggested->concat($more);
        }

        return Inertia::render('Public/Pages/News/Show', [
            'news' => $this->serializeForDetail($news),
            'suggested' => $suggested->map(fn (News $item) => $this->serializeForListing($item))->values()->all(),
        ])->toResponse($request);
    }

    private function serializeForListing(News $news): array
    {
        return [
            'id' => $news->id,
            'title' => $news->title,
            'slug' => $news->slug,
            'excerpt' => $news->excerpt ?: Str::limit(strip_tags((string) $news->content), 160),
            'image_path' => $news->image_path,
            'image_url' => $news->imageUrl(),
            'category' => $news->category,
            'category_label' => $this->categoryLabel($news->category),
            'published_at' => $news->published_at?->toDateString(),
            'published_at_human' => $news->published_at?->format('d M Y'),
            'url' => route('public.news.show', $news->slug),
        ];
    }

    private function serializeForDetail(News $news): array
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
            'category_label' => $this->categoryLabel($news->category),
            'published_at' => $news->published_at?->toDateString(),
            'published_at_human' => $news->published_at?->isoFormat('D MMMM YYYY'),
            'url' => route('public.news.show', $news->slug),
        ];
    }

    private function categoryLabel(?string $category): string
    {
        return match ($category) {
            'announcement' => 'Pengumuman',
            'event' => 'Acara',
            'achievement' => 'Pencapaian',
            'community' => 'Komuniti',
            'education' => 'Pendidikan',
            default => 'Umum',
        };
    }

    private function categoryOptions(): array
    {
        return [
            ['value' => 'general', 'label' => 'Umum'],
            ['value' => 'announcement', 'label' => 'Pengumuman'],
            ['value' => 'event', 'label' => 'Acara'],
            ['value' => 'achievement', 'label' => 'Pencapaian'],
            ['value' => 'community', 'label' => 'Komuniti'],
            ['value' => 'education', 'label' => 'Pendidikan'],
        ];
    }
}