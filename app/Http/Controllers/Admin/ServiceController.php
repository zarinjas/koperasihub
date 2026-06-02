<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Cooperative;
use App\Models\Service;
use App\Services\AuditLogService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $category = trim((string) $request->string('category'));

        $services = Service::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ServiceStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->ordered()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Service $service) => $this->serializeService($service));

        return Inertia::render('Admin/Pages/Services/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
            ],
            'services' => $services,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'categoryOptions' => $this->categoryOptions(includeAll: true),
            'canCreate' => $request->user()?->can(AccessControl::PERMISSION_CREATE_SERVICES) ?? false,
            'canEdit' => $request->user()?->can(AccessControl::PERMISSION_EDIT_SERVICES) ?? false,
            'canDelete' => $request->user()?->can(AccessControl::PERMISSION_DELETE_SERVICES) ?? false,
            'canPublish' => $request->user()?->can(AccessControl::PERMISSION_PUBLISH_SERVICES) ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Pages/Services/Form', [
            'mode' => 'create',
            'serviceRecord' => null,
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function edit(Service $service): Response
    {
        $this->ensureSameCooperative($service);

        return Inertia::render('Admin/Pages/Services/Form', [
            'mode' => 'edit',
            'serviceRecord' => $this->serializeService($service),
            'statusOptions' => $this->statusOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'cooperative_id' => $this->activeCooperative()?->id,
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? $validated['title'],
            'category' => $validated['category'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('services', 'public');
        }

        $service = Service::query()->create($data);

        return redirect()
            ->route('admin.services.edit', $service)
            ->with('status', 'Perkhidmatan berjaya dicipta.');
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->ensureSameCooperative($service);
        $validated = $request->validated();
        $oldValues = $this->serviceAuditSnapshot($service);

        $data = [
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? $validated['title'],
            'category' => $validated['category'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            if ($service->image_path) {
                Storage::disk('public')->delete($service->image_path);
            }

            $data['image_path'] = $request->file('image')->store('services', 'public');
        }

        $service->update($data);
        $this->auditLogs->record('service_updated', $service, $oldValues, $this->serviceAuditSnapshot($service));

        return back()->with('status', 'Perkhidmatan berjaya dikemas kini.');
    }

    public function publish(Service $service): RedirectResponse
    {
        return $this->updateStatus($service, ServiceStatus::Published, 'Perkhidmatan berjaya diterbitkan.');
    }

    public function unpublish(Service $service): RedirectResponse
    {
        return $this->updateStatus($service, ServiceStatus::Draft, 'Perkhidmatan dikembalikan ke draf.');
    }

    public function archive(Service $service): RedirectResponse
    {
        return $this->updateStatus($service, ServiceStatus::Archived, 'Perkhidmatan berjaya diarkibkan.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->ensureSameCooperative($service);
        $oldValues = $this->serviceAuditSnapshot($service);

        if ($service->image_path) {
            Storage::disk('public')->delete($service->image_path);
        }

        $service->delete();
        $this->auditLogs->record('service.deleted', $service, $oldValues, [
            'deleted_at' => $service->deleted_at?->toISOString(),
        ]);

        return redirect()
            ->route('admin.services.index')
            ->with('status', 'Perkhidmatan berjaya dipadam.');
    }

    private function updateStatus(Service $service, ServiceStatus $status, string $message): RedirectResponse
    {
        $this->ensureSameCooperative($service);
        $oldValues = ['status' => $service->status->value];

        $service->update([
            'status' => $status->value,
        ]);
        $this->auditLogs->record(
            match ($status) {
                ServiceStatus::Published => 'service.published',
                ServiceStatus::Archived => 'service.archived',
                ServiceStatus::Draft => 'service.unpublished',
            },
            $service,
            $oldValues,
            ['status' => $service->status->value],
        );

        return back()->with('status', $message);
    }

    private function serviceAuditSnapshot(Service $service): array
    {
        return [
            'title' => $service->title,
            'slug' => $service->slug,
            'status' => $service->status->value,
            'is_featured' => $service->is_featured,
        ];
    }

    private function serializeService(Service $service): array
    {
        return [
            'id' => $service->id,
            'title' => $service->title,
            'slug' => $service->slug,
            'category' => $service->category,
            'summary' => $service->summary,
            'description' => $service->description,
            'image_path' => $service->image_path,
            'image_url' => $service->imageUrl(),
            'icon' => $service->icon,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'whatsapp' => $service->whatsapp,
            'button_text' => $service->button_text,
            'button_url' => $service->button_url,
            'status' => $service->status->value,
            'is_featured' => $service->is_featured,
            'updated_at' => $service->updated_at?->format('d/m/Y H:i'),
            'public_url' => route('public.services.show', $service->slug),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Service $service): void
    {
        abort_unless($service->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $categories = Service::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->map(fn (string $category) => [
                'value' => $category,
                'label' => $this->formatCategoryLabel($category),
            ])
            ->values()
            ->all();

        if ($includeAll) {
            return [['value' => '', 'label' => 'Semua kategori'], ...$categories];
        }

        return [['value' => '', 'label' => 'Pilih kategori'], ...$categories];
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => ServiceStatus::Draft->value, 'label' => 'Draf'],
            ['value' => ServiceStatus::Published->value, 'label' => 'Diterbitkan'],
            ['value' => ServiceStatus::Archived->value, 'label' => 'Diarkibkan'],
        ];

        return $includeAll
            ? [['value' => '', 'label' => 'Semua status'], ...$options]
            : $options;
    }

    private function formatCategoryLabel(string $category): string
    {
        return str($category)->replace('_', ' ')->title()->toString();
    }
}