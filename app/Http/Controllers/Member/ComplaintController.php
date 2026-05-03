<?php

namespace App\Http\Controllers\Member;

use App\Enums\ComplaintPriority;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Requests\Member\StoreComplaintRequest;

class ComplaintController extends MemberPortalController
{
    public function __construct(
        private readonly ComplaintService $complaints,
    ) {}

    public function index(Request $request): Response
    {
        $complaints = Complaint::query()
            ->forCooperative($this->activeCooperativeId($request))
            ->where('created_by', $this->currentUser($request)->id)
            ->withCount(['replies as visible_replies_count' => fn ($query) => $query->where('is_internal', false)])
            ->latest('updated_at')
            ->get()
            ->map(fn (Complaint $complaint) => $this->serializeSummary($complaint))
            ->all();

        return Inertia::render('Member/Pages/Complaints/Index', [
            'memberLinked' => (bool) $this->currentMemberOrNull($request),
            'complaints' => $complaints,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Member/Pages/Complaints/Create', [
            'memberLinked' => (bool) $this->currentMemberOrNull($request),
            'categoryOptions' => $this->categoryOptions(),
            'priorityOptions' => $this->priorityOptions(),
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $complaint = $this->complaints->createForMember($request->validated(), $request->user());

        return redirect()
            ->route('member.complaints.show', $complaint)
            ->with('status', 'Aduan atau cadangan anda berjaya dihantar.');
    }

    public function show(Request $request, Complaint $complaint): Response
    {
        $this->authorize('viewMember', $complaint);
        $complaint->loadMissing(['replies.user', 'member']);

        return Inertia::render('Member/Pages/Complaints/Show', [
            'complaint' => $this->serializeDetailForMember($complaint),
        ]);
    }

    private function serializeSummary(Complaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'ticket_no' => $complaint->ticket_no,
            'category' => $complaint->category,
            'category_label' => $this->categoryLabel($complaint->category),
            'subject' => $complaint->subject,
            'status' => $complaint->status->value,
            'priority' => $complaint->priority->value,
            'visible_replies_count' => $complaint->visible_replies_count,
            'submitted_at' => $complaint->created_at?->format('d/m/Y H:i'),
            'updated_at' => $complaint->updated_at?->format('d/m/Y H:i'),
            'show_url' => route('member.complaints.show', $complaint),
        ];
    }

    private function serializeDetailForMember(Complaint $complaint): array
    {
        return [
            'id' => $complaint->id,
            'ticket_no' => $complaint->ticket_no,
            'category' => $complaint->category,
            'category_label' => $this->categoryLabel($complaint->category),
            'subject' => $complaint->subject,
            'message' => $complaint->message,
            'status' => $complaint->status->value,
            'priority' => $complaint->priority->value,
            'member_name' => $complaint->member?->full_name,
            'submitted_at' => $complaint->created_at?->format('d/m/Y H:i'),
            'closed_at' => $complaint->closed_at?->format('d/m/Y H:i'),
            'replies' => $complaint->replies
                ->where('is_internal', false)
                ->values()
                ->map(fn ($reply) => [
                    'id' => $reply->id,
                    'message' => $reply->message,
                    'author_name' => $reply->user?->name ?? 'Sistem',
                    'is_internal' => false,
                    'created_at' => $reply->created_at?->format('d/m/Y H:i'),
                ])
                ->all(),
        ];
    }

    private function categoryOptions(): array
    {
        return [
            ['value' => 'aduan', 'label' => 'Aduan'],
            ['value' => 'cadangan', 'label' => 'Cadangan'],
            ['value' => 'portal', 'label' => 'Portal ahli'],
            ['value' => 'dokumen', 'label' => 'Dokumen'],
            ['value' => 'keahlian', 'label' => 'Keahlian'],
            ['value' => 'lain_lain', 'label' => 'Lain-lain'],
        ];
    }

    private function priorityOptions(): array
    {
        return [
            ['value' => ComplaintPriority::Low->value, 'label' => 'Rendah'],
            ['value' => ComplaintPriority::Medium->value, 'label' => 'Sederhana'],
            ['value' => ComplaintPriority::High->value, 'label' => 'Tinggi'],
        ];
    }

    private function categoryLabel(?string $value): string
    {
        return collect($this->categoryOptions())
            ->firstWhere('value', $value)['label'] ?? ($value ?: '-');
    }
}
