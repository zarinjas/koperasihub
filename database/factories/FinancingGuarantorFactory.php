<?php

namespace Database\Factories;

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
            'status' => 'pending',
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => fake()->sentence(),
            'responded_at' => now(),
        ]);
    }
}