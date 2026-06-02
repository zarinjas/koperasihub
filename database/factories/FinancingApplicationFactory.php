<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\Member;
use App\Models\FinancingApplication;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingApplicationFactory extends Factory
{
    protected $model = FinancingApplication::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'member_id' => Member::factory(),
            'financing_category_id' => FinancingCategory::factory(),
            'financing_product_id' => FinancingProduct::factory(),
            'reference_no' => 'FIN-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'amount_requested' => fake()->randomFloat(2, 1000, 50000),
            'tenure_months' => fake()->randomElement([12, 24, 36, 48, 60]),
            'purpose' => fake()->sentence(),
            'monthly_income' => fake()->randomFloat(2, 2000, 10000),
            'monthly_commitment' => fake()->randomFloat(2, 0, 3000),
            'status' => 'dihantar',
            'submitted_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draf',
            'submitted_at' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dihantar',
            'submitted_at' => now(),
        ]);
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dalam_proses',
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'berjaya',
            'submitted_at' => now(),
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
            'submitted_at' => now(),
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}