<?php

namespace App\Http\Controllers\Public;

use App\Models\Document;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $categorySlug = trim((string) $request->string('category'));
        $cooperativeId = $this->settings->activeCooperative()?->id;

        $documents = Document::query()
            ->publiclyVisible()
            ->where('cooperative_id', $cooperativeId)
            ->with('category')
            ->when($categorySlug !== '', fn ($query) => $query->whereHas('category', fn ($query) => $query->where('slug', $categorySlug)))
            ->orderByDesc('published_at')
            ->orderBy('title')
            ->get()
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'category' => $document->category?->name,
                'category_slug' => $document->category?->slug,
                'file_name' => $document->file_name,
                'file_size_label' => $this->formatBytes($document->file_size),
                'published_at' => $document->published_at?->format('d/m/Y'),
                'download_url' => route('public.downloads.download', $document),
            ])
            ->values()
            ->all();

        $categories = Document::query()
            ->publiclyVisible()
            ->where('cooperative_id', $cooperativeId)
            ->with('category')
            ->get()
            ->pluck('category')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn ($category) => [
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->all();

        return Inertia::render('Public/Pages/Downloads/Index', [
            'documents' => $documents,
            'categories' => $categories,
            'activeCategory' => $categorySlug,
        ]);
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(
            $document->cooperative_id === $this->settings->activeCooperative()?->id
            && $document->status->value === 'published'
            && $document->visibility->value === 'public'
            && (is_null($document->published_at) || $document->published_at->lte(now()))
            && (is_null($document->expires_at) || $document->expires_at->isFuture()),
            404
        );

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->file_name ?: basename($document->file_path)
        );
    }

    private function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '-';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }
}
