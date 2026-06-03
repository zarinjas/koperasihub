<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Cooperative;
use App\Notifications\AnnouncementNotification;
use App\Services\AnnouncementNotificationService;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
        private readonly AnnouncementNotificationService $notifications,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $audience = $request->string('audience')->toString();

        $announcements = Announcement::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, AnnouncementStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($audience, AnnouncementAudience::values(), true), fn ($query) => $query->where('audience', $audience))
            ->ordered()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Announcement $announcement) => $this->serializeAnnouncement($announcement));

        return Inertia::render('Admin/Pages/Announcements/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'audience' => $audience,
            ],
            'announcements' => $announcements,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'audienceOptions' => $this->audienceOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_ANNOUNCEMENTS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_ANNOUNCEMENTS) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_ANNOUNCEMENTS) ?? false,
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_ANNOUNCEMENTS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Announcements/Form', [
            'mode' => 'create',
            'announcementRecord' => null,
            'audienceOptions' => $this->audienceOptions(),
            'memberSearchUrl' => route('admin.members.search'),
        ]);
    }

    public function edit(Announcement $announcement): Response
    {
        $this->ensureSameCooperative($announcement);

        return Inertia::render('Admin/Pages/Announcements/Form', [
            'mode' => 'edit',
            'announcementRecord' => $this->serializeAnnouncement($announcement),
            'audienceOptions' => $this->audienceOptions(),
            'memberSearchUrl' => route('admin.members.search'),
            'selectedMembers' => $announcement->specificMembers()->get(['members.id', 'members.full_name', 'members.member_no', 'members.email'])->toArray(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'cooperative_id' => $this->activeCooperative()?->id,
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'content' => $validated['content'] ?? null,
            'audience' => $validated['audience'],
            'status' => AnnouncementStatus::Published->value,
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'send_notification' => (bool) ($validated['send_notification'] ?? false),
            'send_email' => (bool) ($validated['send_email'] ?? false),
            'published_at' => now(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement = Announcement::query()->create($data);

        if (! empty($validated['specific_member_ids'])) {
            $announcement->specificMembers()->sync($validated['specific_member_ids']);
        }

        if ($announcement->send_notification && $announcement->status->value === AnnouncementStatus::Published->value) {
            $this->notifications->send($announcement);

            $request->user()->notify(new AnnouncementNotification($announcement));
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('status', 'Pengumuman berjaya dicipta.');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->ensureSameCooperative($announcement);
        $validated = $request->validated();

        $data = [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'content' => $validated['content'] ?? null,
            'audience' => $validated['audience'],
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'send_notification' => (bool) ($validated['send_notification'] ?? false),
            'send_email' => (bool) ($validated['send_email'] ?? false),
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }

            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($data);

        if (array_key_exists('specific_member_ids', $validated)) {
            $announcement->specificMembers()->sync($validated['specific_member_ids'] ?? []);
        }

        return back()->with('status', 'Pengumuman berjaya dikemas kini.');
    }

    public function publish(Announcement $announcement): RedirectResponse
    {
        $this->ensureSameCooperative($announcement);
        $oldValues = [
            'status' => $announcement->status->value,
            'published_at' => $announcement->published_at?->toISOString(),
        ];

        $announcement->update([
            'status' => AnnouncementStatus::Published->value,
            'published_at' => $announcement->published_at ?? now(),
        ]);
        $this->auditLogs->record('announcement_published', $announcement, $oldValues, [
            'status' => $announcement->status->value,
            'published_at' => $announcement->published_at?->toISOString(),
        ]);

        if ($announcement->send_notification) {
            $this->notifications->send($announcement->fresh());
        }

        return back()->with('status', 'Pengumuman berjaya diterbitkan.');
    }

    public function unpublish(Announcement $announcement): RedirectResponse
    {
        return $this->updateStatus($announcement, AnnouncementStatus::Draft, 'Pengumuman dikembalikan ke draf.');
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        return $this->updateStatus($announcement, AnnouncementStatus::Archived, 'Pengumuman berjaya diarkibkan.');
    }

    public function pin(Announcement $announcement): RedirectResponse
    {
        $this->ensureSameCooperative($announcement);
        $oldValues = ['is_pinned' => $announcement->is_pinned];
        $announcement->update(['is_pinned' => true]);
        $this->auditLogs->record('announcement.pinned', $announcement, $oldValues, [
            'is_pinned' => $announcement->is_pinned,
        ]);

        return back()->with('status', 'Pengumuman berjaya dipin.');
    }

    public function unpin(Announcement $announcement): RedirectResponse
    {
        $this->ensureSameCooperative($announcement);
        $oldValues = ['is_pinned' => $announcement->is_pinned];
        $announcement->update(['is_pinned' => false]);
        $this->auditLogs->record('announcement.unpinned', $announcement, $oldValues, [
            'is_pinned' => $announcement->is_pinned,
        ]);

        return back()->with('status', 'Pin pengumuman telah dibuang.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->ensureSameCooperative($announcement);
        $oldValues = $this->announcementAuditSnapshot($announcement);

        if ($announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
        }

        $announcement->delete();
        $this->auditLogs->record('announcement.deleted', $announcement, $oldValues, [
            'deleted_at' => $announcement->deleted_at?->toISOString(),
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('status', 'Pengumuman berjaya dipadam.');
    }

    private function updateStatus(Announcement $announcement, AnnouncementStatus $status, string $message): RedirectResponse
    {
        $this->ensureSameCooperative($announcement);
        $oldValues = ['status' => $announcement->status->value];

        $announcement->update([
            'status' => $status->value,
        ]);
        $this->auditLogs->record(
            match ($status) {
                AnnouncementStatus::Published => 'announcement_published',
                AnnouncementStatus::Archived => 'announcement.archived',
                AnnouncementStatus::Draft => 'announcement.unpublished',
            },
            $announcement,
            $oldValues,
            ['status' => $announcement->status->value],
        );

        return back()->with('status', $message);
    }

    private function announcementAuditSnapshot(Announcement $announcement): array
    {
        return [
            'title' => $announcement->title,
            'slug' => $announcement->slug,
            'status' => $announcement->status->value,
            'audience' => $announcement->audience->value,
            'is_pinned' => $announcement->is_pinned,
        ];
    }

    private function serializeAnnouncement(Announcement $announcement): array
    {
        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'slug' => $announcement->slug,
            'summary' => $announcement->summary,
            'content' => $announcement->content,
            'image_path' => $announcement->image_path,
            'image_url' => $announcement->imageUrl(),
            'audience' => $announcement->audience->value,
            'status' => $announcement->status->value,
            'is_pinned' => $announcement->is_pinned,
            'send_notification' => $announcement->send_notification,
            'send_email' => $announcement->send_email,
            'published_at' => $announcement->published_at?->format('Y-m-d\TH:i'),
            'published_at_human' => $announcement->published_at?->format('d/m/Y H:i'),
            'expires_at' => $announcement->expires_at?->format('Y-m-d\TH:i'),
            'expires_at_human' => $announcement->expires_at?->format('d/m/Y H:i'),
            'updated_at' => $announcement->updated_at?->format('d/m/Y H:i'),
            'public_url' => route('public.announcements.show', $announcement->slug),
            'content_preview' => Str::limit(strip_tags((string) $announcement->content), 140),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Announcement $announcement): void
    {
        abort_unless($announcement->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => AnnouncementStatus::Draft->value, 'label' => 'Draf'],
            ['value' => AnnouncementStatus::Published->value, 'label' => 'Diterbitkan'],
            ['value' => AnnouncementStatus::Archived->value, 'label' => 'Diarkibkan'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }

    private function audienceOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => AnnouncementAudience::Public->value, 'label' => 'Public'],
            ['value' => AnnouncementAudience::Members->value, 'label' => 'Ahli sahaja'],
            ['value' => AnnouncementAudience::Admins->value, 'label' => 'Admin sahaja'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua audiens'], ...$options]
            : $options;
    }
}