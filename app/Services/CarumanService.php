<?php

namespace App\Services;

use App\Models\MemberContribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CarumanService
{
    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {
    }

    public function updateOrCreate(int $cooperativeId, int $memberId, int $year, array $values, User $actor): MemberContribution
    {
        return DB::transaction(function () use ($cooperativeId, $memberId, $year, $values, $actor): MemberContribution {
            $contribution = MemberContribution::query()->firstOrNew([
                'cooperative_id' => $cooperativeId,
                'member_id' => $memberId,
                'year' => $year,
            ]);

            $oldValues = $contribution->exists
                ? $contribution->only(['caruman_semasa', 'caruman_keseluruhan', 'dividen', 'notes'])
                : [];

            $contribution->fill([
                'cooperative_id' => $cooperativeId,
                'member_id' => $memberId,
                'year' => $year,
                'caruman_semasa' => $values['caruman_semasa'] ?? $contribution->caruman_semasa ?? 0,
                'caruman_keseluruhan' => $values['caruman_keseluruhan'] ?? $contribution->caruman_keseluruhan ?? 0,
                'dividen' => $values['dividen'] ?? $contribution->dividen ?? 0,
                'notes' => $values['notes'] ?? $contribution->notes,
            ]);

            if ($contribution->isDirty()) {
                $isNew = ! $contribution->exists;

                if ($isNew) {
                    $contribution->created_by = $actor->id;
                }
                $contribution->updated_by = $actor->id;
                $contribution->save();

                $newValues = $contribution->only(['caruman_semasa', 'caruman_keseluruhan', 'dividen', 'notes']);

                $this->auditLogs->record(
                    'caruman_updated',
                    $contribution,
                    $oldValues,
                    $newValues,
                );
            }

            return $contribution;
        });
    }
}