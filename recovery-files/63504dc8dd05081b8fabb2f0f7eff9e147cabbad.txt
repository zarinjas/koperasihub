<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferralCommissionStatus;
use App\Http\Controllers\Controller;
use App\Models\ReferralCommission;
use App\Services\ReferralCommissionService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReferralCommissionController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly ReferralCommissionService $referralCommissions,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize(AccessControl::PERMISSION_VIEW_REFERRAL_COMMISSIONS);

        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $cooperative = $this->settings->activeCooperative();
        $cooperativeId = $cooperative?->id;

        $commissions = ReferralCommission::query()
            ->forCooperative($cooperativeId)
            ->with(['referrer', 'referredMember', 'membershipApplication', 'paidBy'])
            ->search($search)
            ->when(in_array($status, ReferralCommissionStatus::values(), true), fn ($query) => $query->byStatus($status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ReferralCommission $commission) => [
                'id' => $commission->id,
                'referrer' => [
                    'id' => $commission->referrer->id,
                    'full_name' => $commission->referrer->full_name,
                    'member_no' => $commission->referrer->member_no,
                ],
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
                'paid_by' => $commission->paidBy ? $commission->paidBy->name : null,
                'created_at' => $commission->created_at->toISOString(),
            ]);

        $counts = [
            'pending' => ReferralCommission::query()->forCooperative($cooperativeId)->byStatus(ReferralCommissionStatus::Pending->value)->count(),
            'approved' => ReferralCommission::query()->forCooperative($cooperativeId)->byStatus(ReferralCommissionStatus::Approved->value)->count(),
            'paid' => ReferralCommission::query()->forCooperative($cooperativeId)->byStatus(ReferralCommissionStatus::Paid->value)->count(),
            'cancelled' => ReferralCommission::query()->forCooperative($cooperativeId)->byStatus(ReferralCommissionStatus::Cancelled->value)->count(),
            'total_paid' => (float) ReferralCommission::query()
                ->forCooperative($cooperativeId)
                ->byStatus(ReferralCommissionStatus::Paid->value)
                ->sum('commission_amount'),
        ];

        $referralSettings = $this->settings->group('referral');

        return Inertia::render('Admin/Pages/ReferralCommissions/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'commissions' => $commissions,
            'statusOptions' => $this->statusOptions(),
            'counts' => $counts,
            'canProcessPayment' => $request->user()?->can(AccessControl::PERMISSION_PROCESS_REFERRAL_PAYMENTS) ?? false,
            'canEditSettings' => $request->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS) ?? false,
            'settings' => [
                'commission_amount' => $referralSettings['commission_amount'] ?? '20.00',
                'commission_enabled' => $referralSettings['commission_enabled'] ?? '1',
                'minimum_active_days' => $referralSettings['minimum_active_days'] ?? '0',
            ],
        ]);
    }

    public function show(Request $request, ReferralCommission $commission): Response
    {
        Gate::authorize(AccessControl::PERMISSION_VIEW_REFERRAL_COMMISSIONS);

        $commission->load(['referrer', 'referredMember', 'membershipApplication', 'paidBy']);

        return Inertia::render('Admin/Pages/ReferralCommissions/Show', [
            'commission' => [
                'id' => $commission->id,
                'referrer' => [
                    'id' => $commission->referrer->id,
                    'full_name' => $commission->referrer->full_name,
                    'member_no' => $commission->referrer->member_no,
                    'bank' => $commission->referrer->bank,
                    'bank_account' => $commission->referrer->bank_account,
                    'email' => $commission->referrer->email,
                    'phone' => $commission->referrer->phone,
                ],
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
                'paid_by' => $commission->paidBy?->name,
                'payment_notes' => $commission->payment_notes,
                'created_at' => $commission->created_at->toISOString(),
            ],
            'canProcessPayment' => $request->user()?->can(AccessControl::PERMISSION_PROCESS_REFERRAL_PAYMENTS) ?? false,
        ]);
    }

    public function approve(Request $request, ReferralCommission $commission): RedirectResponse
    {
        Gate::authorize(AccessControl::PERMISSION_PROCESS_REFERRAL_PAYMENTS);

        $this->referralCommissions->markEligible($commission);

        return back()->with('status', 'Komisyen telah diluluskan.');
    }

    public function pay(Request $request, ReferralCommission $commission): RedirectResponse
    {
        Gate::authorize(AccessControl::PERMISSION_PROCESS_REFERRAL_PAYMENTS);

        $validated = $request->validate([
            'payment_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->referralCommissions->pay($commission, $request->user(), $validated['payment_notes'] ?? null);

        return back()->with('status', 'Komisyen telah ditandakan sebagai dibayar.');
    }

    public function cancel(Request $request, ReferralCommission $commission): RedirectResponse
    {
        Gate::authorize(AccessControl::PERMISSION_PROCESS_REFERRAL_PAYMENTS);

        $this->referralCommissions->cancel($commission, $request->user());

        return back()->with('status', 'Komisyen telah dibatalkan.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        Gate::authorize(AccessControl::PERMISSION_EDIT_SETTINGS);

        $validated = $request->validate([
            'commission_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'commission_enabled' => ['nullable', 'in:0,1'],
            'minimum_active_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $cooperative = $this->settings->activeCooperative();
        abort_unless($cooperative, 404);

        $this->settings->update($cooperative, ['referral' => $validated]);

        return back()->with('status', 'Tetapan komisyen berjaya dikemas kini.');
    }

    private function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => ReferralCommissionStatus::Pending->value, 'label' => 'Tertunda'],
            ['value' => ReferralCommissionStatus::Approved->value, 'label' => 'Diluluskan'],
            ['value' => ReferralCommissionStatus::Paid->value, 'label' => 'Dibayar'],
            ['value' => ReferralCommissionStatus::Cancelled->value, 'label' => 'Dibatalkan'],
        ];
    }
}
