<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PosterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePosterRequest;
use App\Http\Requests\Admin\UpdatePosterRequest;
use App\Models\Cooperative;
use App\Models\Poster;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PosterController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        $posters = Poster::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, PosterStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->ordered()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Poster $poster) => $this->serializePoster($poster));

        return Inertia::render('Admin/Pages/Posters/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'posters' => $posters,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_POSTERS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_POSTERS) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_POSTERS) ?? false,
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_POSTERS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Posters/Form', [
            'mode' => 'create',
            'poster' => null,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function edit(Poster $poster): Response
    {
        $this->ensureSameCooperative($poster);

        return Inertia::render('Admin/Pages/Posters/Form', [
            'mode' => 'edit',
            'poster' => $this->serializePoster($poster),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StorePosterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $path = $request->file('image')->store('posters', 'public');

        $poster = Poster::query()->create([
            'cooperative_id' => $this->activeCooperative()?->id,
            'title' => $validated['title'],
            'image_path' => $path,
            'alt_text' => $validated['alt_text'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === PosterStatus::Published->value ? now() : null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->auditLogs->record('poster.created', $poster, [], [
            'title' => $poster->title,
            'status' => $poster->status->value,
        ]);

        return redirect()
            ->route('admin.posters.edit', $poster)
            ->with('status', 'Poster berjaya dimuat naik.');
    }

    public function update(UpdatePosterRequest $request, Poster $poster): RedirectResponse
    {
        $this->ensureSameCooperative($poster);
        $validated = $request->validated();
        $oldValues = ['status' => $poster->status->value, 'title' => $poster->title];

        $data = [
            'title' => $validated['title'],
            'alt_text' => $validated['alt_text'] ?? null,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === PosterStatus::Published->value
                ? ($poster->published_at ?? now())
                : null,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            if ($poster->image_path) {
                Storage::disk('public')->delete($poster->image_path);
            }

            $data['image_path'] = $request->file('image')->store('posters', 'public');
        }

        $poster->update($data);

        $this->auditLogs->record('poster.updated', $poster, $oldValues, [
            'title' => $poster->title,
            'status' => $poster->status->value,
        ]);

        return back()->with('status', 'Poster berjaya dikemas kini.');
    }

    public function publish(Poster $poster): RedirectResponse
    {
        $this->ensureSameCooperative($poster);
        $oldValues = ['status' => $poster->status->value];

        $poster->update([
            'status' => PosterStatus::Published->value,
            'published_at' => $poster->published_at ?? now(),
        ]);

        $this->auditLogs->record('poster.published', $poster, $oldValues, [
            'status' => $poster->status->value,
            'published_at' => $poster->published_at?->toISOString(),
        ]);

        return back()->with('status', 'Poster berjaya diterbitkan.');
    }

    public function unpublish(Poster $poster): RedirectResponse
    {
        return $this->updateStatus($poster, PosterStatus::Draft, 'Poster dikembalikan ke draf.');
    }

    public function destroy(Poster $poster): RedirectResponse
    {
        $this->ensureSameCooperative($poster);
        $oldValues = ['title' => $poster->title, 'status' => $poster->status->value];

        if ($poster->image_path) {
            Storage::disk('public')->delete($poster->image_path);
        }

        $poster->delete();

        $this->auditLogs->record('poster.deleted', $poster, $oldValues, [
            'deleted_at' => $poster->deleted_at?->toISOString(),
        ]);

        return redirect()
            ->route('admin.posters.index')
            ->with('status', 'Poster berjaya dipadam.');
    }

    private function updateStatus(Poster $poster, PosterStatus $status, string $message): RedirectResponse
    {
        $this->ensureSameCooperative($poster);
        $oldValues = ['status' => $poster->status->value];

        $poster->update(['status' => $status->value]);

        $this->auditLogs->record('poster.updated', $poster, $oldValues, [
            'status' => $poster->status->value,
        ]);

        return back()->with('status', $message);
    }

    private function serializePoster(Poster $poster): array
    {
        return [
            'id' => $poster->id,
            'title' => $poster->title,
            'image_path' => $poster->image_path,
            'image_url' => $poster->imageUrl(),
            'alt_text' => $poster->alt_text,
            'status' => $poster->status->value,
            'is_active' => $poster->is_active,
            'published_at' => $poster->published_at?->format('Y-m-d\TH:i'),
            'published_at_human' => $poster->published_at?->format('d/m/Y'),
            'updated_at' => $poster->updated_at?->format('d/m/Y H:i'),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Poster $poster): void
    {
        abort_unless($poster->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => PosterStatus::Draft->value, 'label' => 'Draf'],
            ['value' => PosterStatus::Published->value, 'label' => 'Diterbitkan'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }
}