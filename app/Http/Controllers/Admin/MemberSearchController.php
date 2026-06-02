<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use App\Models\Member;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberSearchController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->string('q'));

        $members = Member::query()
            ->where('cooperative_id', $this->activeCooperative()?->id)
            ->where('membership_status', 'active')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->limit(20)
            ->get(['id', 'full_name', 'member_no', 'email']);

        return response()->json($members);
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }
}