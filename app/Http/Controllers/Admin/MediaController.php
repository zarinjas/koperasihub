<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaFileRequest;
use App\Models\Cooperative;
use App\Models\MediaFile;
use App\Services\Files\MediaFileService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly MediaFileService $mediaFiles,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $collection = trim((string) $request->string('collection'));

        $mediaFiles = MediaFile::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('original_name', 'like', "%{$search}%")
                        ->orWhere('alt_text', 'like', "%{$search}%")
                        ->orWhere('caption', 'like', "%{$search}%");
                });
            })
            ->when($collection !== '', fn ($query) => $query->where('collection', $collection))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (MediaFile $media) => [
                'id' => $media->id,
                'original_name' => $media->original_name,
                'collection' => $media->collection,
                'alt_text' => $media->alt_text,
                'caption' => $media->caption,
                'visibility' => $media->visibility->value,
                'size_label' => $this->formatBytes($media->size),
                'url' => $media->publicUrl(),
                'path' => $media->path,
                'updated_at' => $media->updated_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Pages/Media/Index', [
            'filters' => [
                'search' => $search,
                'collection' => $collection,
            ],
            'mediaFiles' => $mediaFiles,
            'collectionOptions' => [
                ['value' => '', 'label' => 'Semua koleksi'],
                ['value' => 'general', 'label' => 'Umum'],
                ['value' => 'banner', 'label' => 'Banner'],
                ['value' => 'icon', 'label' => 'Ikon'],
                ['value' => 'logo', 'label' => 'Logo'],
            ],
            'visibilityOptions' => [
                ['value' => MediaVisibility::Public->value, 'label' => 'Public'],
            ],
            'canUpload' => $request->user()?->can(AccessControl::PERMISSION_UPLOAD_MEDIA) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_MEDIA) ?? false,
        ]);
    }

    public function store(StoreMediaFileRequest $request): RedirectResponse
    {
        $this->mediaFiles->store($request->file('file'), $request->user(), $request->validated());

        return back()->with('status', 'Media berjaya dimuat naik.');
    }

    public function destroy(MediaFile $media): RedirectResponse
    {
        $this->ensureSameCooperative($media->cooperative_id);

        if ($media->disk === 'public') {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($media->path);
        }

        $media->delete();

        return back()->with('status', 'Media berjaya dipadam.');
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(?int $cooperativeId): void
    {
        abort_unless($cooperativeId && $cooperativeId === $this->activeCooperative()?->id, 404);
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
