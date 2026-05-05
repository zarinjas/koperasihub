<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingApplicationHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingApplicationHistoryFactory extends Factory
{
    protected $model = FinancingApplicationHistory::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'financing_application_id' => FinancingApplication::factory(),
            'actor_id' => User::factory(),
            'action' => 'submitted',
            'from_status' => null,
            'to_status' => 'submitted',
            'notes' => fake()->sentence(),
            'metadata' => [],
        ];
    }
}
