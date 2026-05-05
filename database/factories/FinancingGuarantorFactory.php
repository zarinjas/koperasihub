<?php

namespace Database\Factories;

use App\Enums\FinancingGuarantorStatus;
use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingGuarantor;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingGuarantorFactory extends Factory
{
    protected $model = FinancingGuarantor::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'financing_application_id' => FinancingApplication::factory(),
            'guarantor_member_id' => Member::factory(),
            'status' => FinancingGuarantorStatus::Pending->value,
            'consent_text' => null,
            'consented_at' => null,
            'signature_path' => null,
            'rejection_reason' => null,
            'responded_at' => null,
        ];
    }
}
