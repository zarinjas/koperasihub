<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePopupRequest;
use App\Http\Requests\Admin\UpdatePopupRequest;
use App\Models\Cooperative;
use App\Models\Popup;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PopupController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();

        $popups = Popup::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status): void {
                $query->where('is_active', $status === 'active');
            })
            ->ordered()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Popup $popup) => $this->serializePopup($popup));

        return Inertia::render('Admin/Pages/Popups/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'popups' => $popups,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_POPUPS) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_POPUPS) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_POPUPS) ?? false,
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_POPUPS) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Popups/Form', [
            'mode' => 'create',
            'popup' => null,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function edit(Popup $popup): Response
    {
        $this->ensureSameCooperative($popup);

        return Inertia::render('Admin/Pages/Popups/Form', [
            'mode' => 'edit',
            'popup' => $this->serializePopup($popup),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StorePopupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'cooperative_id' => $this->activeCooperative()?->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('popups', 'public');
        }

        $popup = Popup::query()->create($data);

        $this->auditLogs->record('popup.created', $popup, [], [
            'title' => $popup->title,
            'is_active' => $popup->is_active,
        ]);

        return redirect()
            ->route('admin.popups.edit', $popup)
            ->with('status', 'Popup berjaya dicipta.');
    }

    public function update(UpdatePopupRequest $request, Popup $popup): RedirectResponse
    {
        $this->ensureSameCooperative($popup);
        $validated = $request->validated();
        $oldValues = ['title' => $popup->title, 'is_active' => $popup->is_active];

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            if ($popup->image_path) {
                Storage::disk('public')->delete($popup->image_path);
            }

            $data['image_path'] = $request->file('image')->store('popups', 'public');
        }

        $popup->update($data);

        $this->auditLogs->record('popup.updated', $popup, $oldValues, [
            'title' => $popup->title,
            'is_active' => $popup->is_active,
        ]);

        return back()->with('status', 'Popup berjaya dikemas kini.');
    }

    public function publish(Popup $popup): RedirectResponse
    {
        $this->ensureSameCooperative($popup);
        $oldValues = ['is_active' => $popup->is_active];

        $popup->update(['is_active' => true]);

        $this->auditLogs->record('popup.published', $popup, $oldValues, [
            'title' => $popup->title,
        ]);

        return back()->with('status', 'Popup berjaya diterbitkan.');
    }

    public function unpublish(Popup $popup): RedirectResponse
    {
        $this->ensureSameCooperative($popup);
        $oldValues = ['is_active' => $popup->is_active];

        $popup->update(['is_active' => false]);

        $this->auditLogs->record('popup.unpublished', $popup, $oldValues, [
            'title' => $popup->title,
        ]);

        return back()->with('status', 'Popup dikembalikan ke draf.');
    }

    public function destroy(Popup $popup): RedirectResponse
    {
        $this->ensureSameCooperative($popup);
        $oldValues = ['title' => $popup->title];

        if ($popup->image_path) {
            Storage::disk('public')->delete($popup->image_path);
        }

        $popup->delete();

        $this->auditLogs->record('popup.deleted', $popup, $oldValues, [
            'deleted_at' => $popup->deleted_at?->toISOString(),
        ]);

        return redirect()
            ->route('admin.popups.index')
            ->with('status', 'Popup berjaya dipadam.');
    }

    private function serializePopup(Popup $popup): array
    {
        return [
            'id' => $popup->id,
            'title' => $popup->title,
            'content' => $popup->content,
            'image_path' => $popup->image_path,
            'image_url' => $popup->imageUrl(),
            'button_text' => $popup->button_text,
            'button_url' => $popup->button_url,
            'is_active' => $popup->is_active,
            'starts_at' => $popup->starts_at?->format('Y-m-d\TH:i'),
            'starts_at_human' => $popup->starts_at?->format('d/m/Y'),
            'ends_at' => $popup->ends_at?->format('Y-m-d\TH:i'),
            'ends_at_human' => $popup->ends_at?->format('d/m/Y'),
            'updated_at' => $popup->updated_at?->format('d/m/Y H:i'),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Popup $popup): void
    {
        abort_unless($popup->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => 'active', 'label' => 'Aktif'],
            ['value' => 'inactive', 'label' => 'Tidak Aktif'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }
}
