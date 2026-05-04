<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\User;
use Illuminate\Support\Carbon;

class ComplaintService
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {}

    public function createForMember(array $data, User $user): Complaint
    {
        $member = $user->member;

        $complaint = Complaint::query()->create([
            'cooperative_id' => $user->cooperative_id,
            'member_id' => $member?->id,
            'created_by' => $user->id,
            'ticket_no' => $this->nextTicketNumber(),
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'priority' => $data['priority'],
            'status' => ComplaintStatus::Open->value,
        ]);

        $this->auditLogs->record('complaint_submitted', $complaint, [], $this->stateSnapshot($complaint));

        return $complaint;
    }

    public function updateFromAdmin(Complaint $complaint, array $data, User $actor): void
    {
        $oldValues = $this->stateSnapshot($complaint);

        $complaint->update([
            'status' => $data['status'],
            'priority' => $data['priority'],
            'assigned_to' => $data['assigned_to'] ?: null,
            'closed_at' => $data['status'] === ComplaintStatus::Closed->value
                ? ($complaint->closed_at ?? now())
                : null,
        ]);

        $newValues = $this->stateSnapshot($complaint);

        $this->auditLogs->record('complaint_status_changed', $complaint, $oldValues, $newValues, [
            'actor_id' => $actor->id,
        ]);

        if ($complaint->status->value === ComplaintStatus::Closed->value) {
            $this->auditLogs->record('complaint_closed', $complaint, $oldValues, $newValues, [
                'actor_id' => $actor->id,
            ]);
        }
    }

    public function addAdminReply(Complaint $complaint, array $data, User $actor): ComplaintReply
    {
        $reply = $complaint->replies()->create([
            'user_id' => $actor->id,
            'message' => $data['message'],
            'is_internal' => (bool) ($data['is_internal'] ?? false),
        ]);

        $this->auditLogs->record(
            $reply->is_internal ? 'complaint_internal_note_added' : 'complaint_replied',
            $complaint,
            [],
            ['reply_id' => $reply->id],
            ['actor_id' => $actor->id, 'is_internal' => $reply->is_internal],
        );

        return $reply;
    }

    private function nextTicketNumber(): string
    {
        $prefix = 'ADU-'.Carbon::now()->format('Ymd').'-';

        $lastTicket = Complaint::query()
            ->withTrashed()
            ->where('ticket_no', 'like', $prefix.'%')
            ->latest('id')
            ->value('ticket_no');

        $sequence = $lastTicket
            ? ((int) substr($lastTicket, -4)) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function stateSnapshot(Complaint $complaint): array
    {
        return [
            'status' => $complaint->status->value,
            'priority' => $complaint->priority->value,
            'assigned_to' => $complaint->assigned_to,
            'closed_at' => $complaint->closed_at?->toISOString(),
        ];
    }
}
