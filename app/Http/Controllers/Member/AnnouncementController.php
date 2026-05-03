<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $cooperativeId = $this->activeCooperativeId($request);

        $announcements = Announcement::query()
            ->published()
            ->ordered()
            ->where('cooperative_id', $cooperativeId)
            ->whereIn('audience', [
                AnnouncementAudience::Public->value,
                AnnouncementAudience::Members->value,
            ])
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'summary' => $announcement->summary,
                'content_preview' => Str::limit(strip_tags((string) ($announcement->summary ?: $announcement->content)), 180),
                'audience' => $announcement->audience->value,
                'is_pinned' => $announcement->is_pinned,
                'published_at' => $announcement->published_at?->format('d/m/Y'),
            ])
            ->all();

        return Inertia::render('Member/Pages/Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }
}
