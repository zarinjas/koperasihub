<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingProductFactory extends Factory
{
    protected $model = FinancingProduct::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'financing_category_id' => FinancingCategory::factory(),
            'name' => fake()->unique()->words(3, true),
            'slug' => fn (array $attributes) => str()->slug($attributes['name'] ?? fake()->unique()->words(3, true)),
            'description' => fake()->paragraph(),
            'min_amount' => fake()->randomElement([1000, 2000, 5000]),
            'max_amount' => fake()->randomElement([10000, 20000, 50000, 100000]),
            'min_tenure_months' => fake()->randomElement([6, 12]),
            'max_tenure_months' => fake()->randomElement([36, 48, 60]),
            'annual_rate_percent' => fake()->randomFloat(2, 3, 10),
            'rate_note' => 'Kadar keuntungan adalah tetap sepanjang tempoh pembiayaan.',
            'requires_guarantor' => false,
            'guarantor_count' => 1,
            'requires_stamped_upload' => false,
            'is_active' => true,
            'sort_order' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function withGuarantor(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_guarantor' => true,
            'guarantor_count' => $count,
        ]);
    }

    public function withStampedUpload(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_stamped_upload' => true,
            'stamped_upload_instructions' => 'Sila muat naik borang yang telah dicop oleh ketua jabatan.',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}