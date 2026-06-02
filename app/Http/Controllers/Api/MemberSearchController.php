<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            'id' => ['nullable', 'integer'],
        ]);

        $cooperative = app(SettingsService::class)->activeCooperative();

        if (! $cooperative) {
            return response()->json(['data' => []]);
        }

        $query = Member::query()
            ->forCooperative($cooperative->id)
            ->select(['id', 'full_name', 'member_no', 'identity_no'])
            ->limit(20);

        if ($request->filled('id')) {
            $query->where('id', $request->input('id'));
        } elseif ($request->filled('code')) {
            $query->where('referral_code', $request->input('code'));
        } else {
            $search = $request->input('q');
            if (filled($search)) {
                $query->search($search);
            }
        }

        return response()->json([
            'data' => $query->get()->map(fn (Member $member) => [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'member_no' => $member->member_no,
            ]),
        ]);
    }
}