<?php

namespace App\Console\Commands;

use App\Services\ReferralCommissionService;
use Illuminate\Console\Command;

class AutoApproveReferralCommissions extends Command
{
    protected $signature = 'referral:auto-approve';
    protected $description = 'Auto-approve eligible referral commissions that have passed their minimum active period.';

    public function handle(ReferralCommissionService $service): int
    {
        $count = $service->autoApproveEligible();

        $this->info("{$count} komisyen rujukan telah diluluskan secara automatik.");

        return self::SUCCESS;
    }
}