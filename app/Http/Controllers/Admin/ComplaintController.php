<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreComplaintReplyRequest;
use App\Http\Requests\Admin\UpdateComplaintRequest;
use App\Models\Complaint;
use App\Models\Cooperative;
use App\Models\User;
use App\Services\ComplaintService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly ComplaintService $complaints,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $category = $request->string('category')->toString();

        $complaints = Complaint::query()
            ->forCooperative($this->activeCooperative()?->id)
            ->with(['member', 'creator', 'assignee'])
            ->search($search)
            ->when(in_array($status, ComplaintStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($priority, ComplaintPriority::values(), true), fn ($query) => $query->where('priority', $priority))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Complaint $complaint) => $this->serializeSummary($complaint));

        return Inertia::render('Admin/Pages/Complaints/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'priority' => $priority,
                'category' => $category,
            ],
            'complaints' => $complaints,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'priorityOptions' => $this->priorityOptions(includeAll: true),
            'categoryOptions' => $this->categoryOptions(includeAll: true),
            'canReply' => $request->user()?->can(AccessControl::PERMISSION_REPLY_COMPLAINTS) ?? false,
            'canClose' => $request->user()?->can(AccessControl::PERMISSION_CLOSE_COMPLAINTS) ?? false,
        ]);
    }

    public function show(Request $request, Complaint $complaint): Response
    {
        $this->ensureSameCooperative($complaint);
        $complaint->loadMissing(['member', 'creator', 'assignee', 'replies.user']);

        return Inertia::render('Admin/Pages/Complaints/Show', [
            'complaint' => $this->serializeDetail($complaint),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'assigneeOptions' => $this->assigneeOptions($request),
            'canReply' => $request->user()?->can(AccessControl::PERMISSION_REPLY_COMPLAINTS) ?? false,
            'canClose' => $request->user()?->can(AccessControl::PERMISSION_CLOSE_COMPLAINTS) ?? false,
        ]);
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint): RedirectResponse
    {
        $this->ensureSameCooperative($complaint);
        $this->complaints->updateFromAdmin($complaint, $request->validated(), $request->user());

        return back()->with('status', 'Aduan berjaya dikemas kini.');
    }

    public function reply(StoreComplaintReplyRequest $request, Complaint $complaint): RedirectResponse
    {
        $this->ensureSameCooperative($complaint);

        $this->complaints->addAdminReply($complaint, $request->validated(), $request->user());

        return back()->with('status', 'Balasan berjaya disimpan.');
    }

    private function serializeSummary(Complaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'ticket_no' => $complaint->ticket_no,
            'member_name' => $complaint->member?->full_name ?? $complaint->creator?->name ?? 'Ahli portal',
            'category' => $complaint->category,
            'category_label' => $this->categoryLabel($complaint->category),
            'subject' => $complaint->subject,
            'status' => $complaint->status->value,
            'priority' => $complaint->priority->value,
            'assigned_to_name' => $complaint->assignee?->name,
            'updated_at' => $complaint->updated_at?->format('d/m/Y H:i'),
            'show_url' => route('admin.complaints.show', $complaint),
        ];
    }

    private function serializeDetail(Complaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'ticket_no' => $complaint->ticket_no,
            'member_id' => $complaint->member_id,
            'member_name' => $complaint->member?->full_name ?? $complaint->creator?->name ?? 'Ahli portal',
            'member_email' => $complaint->member?->email ?? $complaint->creator?->email,
            'member_phone' => $complaint->member?->phone ?? $complaint->creator?->phone,
            'category' => $complaint->category,
            'category_label' => $this->categoryLabel($complaint->category),
            'subject' => $complaint->subject,
            'message' => $complaint->message,
            'status' => $complaint->status->value,
            'priority' => $complaint->priority->value,
            'assigned_to' => $complaint->assigned_to ? (string) $complaint->assigned_to : '',
            'assigned_to_name' => $complaint->assignee?->name,
            'submitted_at' => $complaint->created_at?->format('d/m/Y H:i'),
            'closed_at' => $complaint->closed_at?->format('d/m/Y H:i'),
            'replies' => $complaint->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'message' => $reply->message,
                'author_name' => $reply->user?->name ?? 'Sistem',
                'is_internal' => $reply->is_internal,
                'created_at' => $reply->created_at?->format('d/m/Y H:i'),
            ])->all(),
        ];
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }

    private function ensureSameCooperative(Complaint $complaint): void
    {
        abort_unless($complaint->cooperative_id === $this->activeCooperative()?->id, 404);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => ComplaintStatus::Open->value, 'label' => 'Terbuka'],
            ['value' => ComplaintStatus::InProgress->value, 'label' => 'Dalam tindakan'],
            ['value' => ComplaintStatus::Resolved->value, 'label' => 'Selesai'],
            ['value' => ComplaintStatus::Closed->value, 'label' => 'Ditutup'],
        ];

        return $includeAll ? [['value' => '', 'label' => 'Semua status'], ...$options] : $options;
    }

    private function priorityOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => ComplaintPriority::Low->value, 'label' => 'Rendah'],
            ['value' => ComplaintPriority::Medium->value, 'label' => 'Sederhana'],
            ['value' => ComplaintPriority::High->value, 'label' => 'Tinggi'],
        ];

        return $includeAll ? [['value' => '', 'label' => 'Semua keutamaan'], ...$options] : $options;
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = [
            ['value' => 'aduan', 'label' => 'Aduan'],
            ['value' => 'cadangan', 'label' => 'Cadangan'],
            ['value' => 'portal', 'label' => 'Portal ahli'],
            ['value' => 'dokumen', 'label' => 'Dokumen'],
            ['value' => 'keahlian', 'label' => 'Keahlian'],
            ['value' => 'lain_lain', 'label' => 'Lain-lain'],
        ];

        return $includeAll ? [['value' => '', 'label' => 'Semua kategori'], ...$options] : $options;
    }

    private function categoryLabel(?string $value): string
    {
        return collect($this->categoryOptions())
            ->firstWhere('value', $value)['label'] ?? ($value ?: '-');
    }

    private function assigneeOptions(Request $request): array
    {
        return User::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->whereIn('user_type', [AccessControl::ROLE_SUPER_ADMIN, AccessControl::ROLE_ADMIN])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ])
            ->prepend(['value' => '', 'label' => 'Belum ditetapkan'])
            ->values()
            ->all();
    }
}
