<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogService
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function record(string $action, ?Model $subject = null, array $oldValues = [], array $newValues = [], array $metadata = []): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $actor = $this->request->user();
        $cooperativeId = $subject?->getAttribute('cooperative_id') ?? $actor?->cooperative_id;

        AuditLog::query()->create([
            'cooperative_id' => $cooperativeId,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata' => $metadata ?: null,
        ]);
    }
}
