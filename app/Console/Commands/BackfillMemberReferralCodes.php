<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\ReferralCommissionService;
use Illuminate\Console\Command;

class BackfillMemberReferralCodes extends Command
{
    protected $signature = 'referral:backfill-codes';
    protected $description = 'Generate referral codes for all existing members that do not have one yet.';

    public function handle(ReferralCommissionService $service): int
    {
        $members = Member::query()
            ->withTrashed()
            ->whereNull('referral_code')
            ->cursor();

        $count = 0;

        foreach ($members as $member) {
            $service->generateReferralCode($member);
            $count++;
        }

        $this->info("{$count} kod rujukan telah dijana untuk ahli sedia ada.");

        return self::SUCCESS;
    }
}
