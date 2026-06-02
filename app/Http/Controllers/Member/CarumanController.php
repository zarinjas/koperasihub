<?php

namespace App\Http\Controllers\Member;

use App\Models\Member;
use App\Models\MemberContribution;
use Illuminate\Http\Request;
use Inertia\Response;

class CarumanController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $cooperativeId = $this->activeCooperativeId($request);
        $member = $this->currentMemberOrNull($request);

        $year = (int) $request->input('year', (int) now()->format('Y'));

        $contribution = MemberContribution::query()
            ->forCooperative($cooperativeId)
            ->where('member_id', $member?->id)
            ->year($year)
            ->first();

        $allYears = MemberContribution::query()
            ->forCooperative($cooperativeId)
            ->where('member_id', $member?->id)
            ->pluck('year')
            ->unique()
            ->sort()
            ->values();

        if ($allYears->isEmpty()) {
            $allYears = collect([$year]);
        } elseif (! $allYears->contains($year)) {
            $allYears->push($year);
        }

        return inertia('Member/Pages/Caruman/Index', [
            'member' => $this->serializeMemberForMemberPage($member),
            'contribution' => $contribution ? $this->serializeContribution($contribution) : null,
            'years' => $allYears->values(),
            'selectedYear' => $year,
        ]);
    }

    private function serializeContribution(MemberContribution $contribution): array
    {
        return [
            'id' => $contribution->id,
            'year' => $contribution->year,
            'caruman_semasa' => (float) $contribution->caruman_semasa,
            'caruman_keseluruhan' => (float) $contribution->caruman_keseluruhan,
            'dividen' => (float) $contribution->dividen,
        ];
    }

    private function serializeMemberForMemberPage(?Member $member): ?array
    {
        if (! $member) {
            return null;
        }

        return [
            'id' => $member->id,
            'member_no' => $member->member_no,
            'full_name' => $member->full_name,
            'membership_status' => $member->membership_status,
        ];
    }
}