<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationRoutingService
{
    public function recipients(?int $unitId, int $cooperativeId): Collection
    {
        $superAdmins = User::role('super_admin')
            ->where('cooperative_id', $cooperativeId)
            ->get();

        if ($unitId) {
            $unitAdmins = User::role('admin')
                ->where('unit_id', $unitId)
                ->where('cooperative_id', $cooperativeId)
                ->get();
        } else {
            $unitAdmins = User::role('admin')
                ->where('cooperative_id', $cooperativeId)
                ->get();
        }

        return $superAdmins->merge($unitAdmins)->unique('id');
    }
}
