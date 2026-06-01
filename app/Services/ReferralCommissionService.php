<?php

namespace App\Services;

use App\Enums\ReferralCommissionStatus;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Notifications\ReferralCommissionEarned;
use App\Notifications\ReferralCommissionPaid;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ReferralCommissionService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function generateReferralCode(Member $member): string
    {
        do {
            $code = sprintf('REF-%s', Str::upper(Str::random(8)));
        } while (Member::query()->withTrashed()->where('referral_code', $code)->exists());

        $member->update(['referral_code' => $code]);

        return $code;
    }

    public function createCommission(MembershipApplication $application, Member $referredMember): ?ReferralCommission
    {
        if (! $application->referred_by_member_id) {
            return null;
        }

        $commissionEnabled = $this->getSetting('commission_enabled', '1');
        if ($commissionEnabled !== '1') {
            return null;
        }

        $referrer = Member::query()->find($application->referred_by_member_id);
        if (! $referrer) {
            return null;
        }

        if ($referrer->id === $referredMember->id) {
            return null;
        }

        $commissionAmount = (float) $this->getSetting('commission_amount', '20.00');
        $minimumActiveDays = (int) $this->getSetting('minimum_active_days', '0');

        $eligibleAt = $minimumActiveDays > 0
            ? $application->reviewed_at?->copy()->addDays($minimumActiveDays)
            : now();

        $status = $minimumActiveDays > 0
            ? ReferralCommissionStatus::Pending
            : ReferralCommissionStatus::Approved;

        return DB::transaction(function () use ($application, $referrer, $referredMember, $commissionAmount, $eligibleAt, $status): ReferralCommission {
            $snapshot = [
                'referrer_bank' => $referrer->bank,
                'referrer_bank_account' => $referrer->bank_account,
                'referrer_name' => $referrer->full_name,
                'referred_name' => $referredMember->full_name,
            ];

            $commission = ReferralCommission::query()->create([
                'cooperative_id' => $application->cooperative_id,
                'referrer_member_id' => $referrer->id,
                'referred_member_id' => $referredMember->id,
                'membership_application_id' => $application->id,
                'commission_amount' => $commissionAmount,
                'status' => $status->value,
                'eligible_at' => $eligibleAt,
                'metadata' => $snapshot,
            ]);

            $this->auditLogs->record(
                'referral_commission_created',
                $commission,
                [],
                ['status' => $status->value, 'commission_amount' => $commissionAmount],
            );

            if ($status === ReferralCommissionStatus::Approved) {
                $referrer->user?->notify(new ReferralCommissionEarned($commission));
            }

            return $commission;
        });
    }

    public function markEligible(ReferralCommission $commission): ReferralCommission
    {
        if ($commission->status !== ReferralCommissionStatus::Pending) {
            throw new RuntimeException('Komisyen tidak dalam status tertunda.');
        }

        return DB::transaction(function () use ($commission): ReferralCommission {
            $commission->update(['status' => ReferralCommissionStatus::Approved->value]);

            $this->auditLogs->record(
                'referral_commission_eligible',
                $commission,
                ['status' => ReferralCommissionStatus::Pending->value],
                ['status' => ReferralCommissionStatus::Approved->value],
            );

            $commission->referrer->user?->notify(new ReferralCommissionEarned($commission));

            return $commission->refresh();
        });
    }

    public function pay(ReferralCommission $commission, User $admin, ?string $notes = null): ReferralCommission
    {
        if (! in_array($commission->status, [ReferralCommissionStatus::Approved], true)) {
            throw new RuntimeException('Komisyen tidak dalam status diluluskan.');
        }

        return DB::transaction(function () use ($commission, $admin, $notes): ReferralCommission {
            $oldStatus = $commission->status->value;

            $commission->update([
                'status' => ReferralCommissionStatus::Paid->value,
                'paid_at' => now(),
                'paid_by' => $admin->id,
                'payment_notes' => $notes,
            ]);

            $this->auditLogs->record(
                'referral_commission_paid',
                $commission,
                ['status' => $oldStatus],
                ['status' => ReferralCommissionStatus::Paid->value, 'paid_by' => $admin->id],
            );

            $commission->referrer->user?->notify(new ReferralCommissionPaid($commission));

            return $commission->refresh();
        });
    }

    public function cancel(ReferralCommission $commission, User $admin): ReferralCommission
    {
        if (in_array($commission->status, [ReferralCommissionStatus::Paid], true)) {
            throw new RuntimeException('Komisyen yang telah dibayar tidak boleh dibatalkan.');
        }

        return DB::transaction(function () use ($commission, $admin): ReferralCommission {
            $oldStatus = $commission->status->value;

            $commission->update(['status' => ReferralCommissionStatus::Cancelled->value]);

            $this->auditLogs->record(
                'referral_commission_cancelled',
                $commission,
                ['status' => $oldStatus],
                ['status' => ReferralCommissionStatus::Cancelled->value, 'cancelled_by' => $admin->id],
            );

            return $commission->refresh();
        });
    }

    public function autoApproveEligible(): int
    {
        $commissions = ReferralCommission::query()
            ->where('status', ReferralCommissionStatus::Pending->value)
            ->whereNotNull('eligible_at')
            ->where('eligible_at', '<=', now())
            ->cursor();

        $count = 0;

        foreach ($commissions as $commission) {
            try {
                $this->markEligible($commission);
                $count++;
            } catch (RuntimeException) {
                continue;
            }
        }

        return $count;
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $group = $this->settings->group('referral');

        return $group[$key] ?? $default;
    }
}
