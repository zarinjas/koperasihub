<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\MemberContribution;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberContributionFactory extends Factory
{
    protected $model = MemberContribution::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'member_id' => Member::factory(),
            'year' => $this->faker->numberBetween(2023, 2026),
            'caruman_semasa' => $this->faker->randomFloat(2, 100, 10000),
            'caruman_keseluruhan' => $this->faker->randomFloat(2, 5000, 50000),
            'dividen' => $this->faker->randomFloat(2, 50, 5000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}