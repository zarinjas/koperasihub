<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ReferralCommission;
use App\Services\ReferralCommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralCommissionService $referralCommissions,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('member_access');

        $member = $request->user()->member;

        if (! $member) {
            abort(403);
        }

        $commissions = ReferralCommission::query()
            ->where('referrer_member_id', $member->id)
            ->with(['referredMember', 'membershipApplication'])
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ReferralCommission $commission) => [
                'id' => $commission->id,
                'referred_member' => [
                    'id' => $commission->referredMember->id,
                    'full_name' => $commission->referredMember->full_name,
                    'member_no' => $commission->referredMember->member_no,
                ],
                'application_no' => $commission->membershipApplication->application_no,
                'commission_amount' => (float) $commission->commission_amount,
                'status' => $commission->status->value,
                'eligible_at' => $commission->eligible_at?->toISOString(),
                'paid_at' => $commission->paid_at?->toISOString(),
                'created_at' => $commission->created_at->toISOString(),
            ]);

        $stats = [
            'total_referrals' => ReferralCommission::query()->where('referrer_member_id', $member->id)->count(),
            'total_earned' => (float) ReferralCommission::query()
                ->where('referrer_member_id', $member->id)
                ->whereIn('status', ['approved', 'paid'])
                ->sum('commission_amount'),
            'total_paid' => (float) ReferralCommission::query()
                ->where('referrer_member_id', $member->id)
                ->where('status', 'paid')
                ->sum('commission_amount'),
        ];

        return Inertia::render('Member/Pages/Referrals/Index', [
            'commissions' => $commissions,
            'stats' => $stats,
            'referral_code' => $member->referral_code,
            'referral_link' => $member->referral_code
                ? route('public.membership.apply') . '?ref=' . $member->referral_code
                : null,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        Gate::authorize('member_access');

        $member = $request->user()->member;

        if (! $member) {
            abort(403);
        }

        if ($member->referral_code) {
            return back()->with('status', 'Kod rujukan anda sudah dijana.');
        }

        $this->referralCommissions->generateReferralCode($member);

        return back()->with('status', 'Kod rujukan berjaya dijana. Anda kini boleh berkongsi!');
    }
}
