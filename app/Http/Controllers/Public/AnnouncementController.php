<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $announcements = Announcement::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->visibleToPublic()
            ->ordered()
            ->get()
            ->map(fn (Announcement $announcement) => $this->serializeAnnouncement($announcement))
            ->all();

        return Inertia::render('Public/Pages/Announcements/Index', [
            'announcements' => $announcements,
        ])->toResponse($request);
    }

    public function show(Request $request, string $slug): Response
    {
        $announcement = Announcement::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->forPublicSlug($slug)
            ->first();

        abort_unless($announcement, 404);

        return Inertia::render('Public/Pages/Announcements/Show', [
            'announcement' => $this->serializeAnnouncement($announcement, includeBody: true),
        ])->toResponse($request);
    }

    private function serializeAnnouncement(Announcement $announcement, bool $includeBody = false): array
    {
        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'slug' => $announcement->slug,
            'summary' => $announcement->summary,
            'content' => $includeBody ? $announcement->content : null,
            'content_preview' => Str::limit(strip_tags((string) ($announcement->summary ?: $announcement->content)), 160),
            'image_path' => $announcement->image_path,
            'image_url' => $announcement->imageUrl(),
            'is_pinned' => $announcement->is_pinned,
            'published_at' => $announcement->published_at?->format('d/m/Y'),
            'detail_url' => '/pengumuman/'.$announcement->slug,
        ];
    }
}