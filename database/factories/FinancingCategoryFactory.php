<?php

namespace Database\Factories;

use App\Enums\FinancingCategoryType;
use App\Models\Cooperative;
use App\Models\FinancingCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingCategoryFactory extends Factory
{
    protected $model = FinancingCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Pembiayaan Berpenjamin',
            'Pembiayaan Tanpa Penjamin',
        ]);

        return [
            'cooperative_id' => Cooperative::factory(),
            'name' => $name,
            'slug' => str()->slug($name),
            'description' => fake()->sentence(),
            'type' => $name === 'Pembiayaan Berpenjamin'
                ? FinancingCategoryType::Guaranteed->value
                : FinancingCategoryType::NonGuaranteed->value,
            'icon' => 'HandCoins',
            'is_active' => true,
            'sort_order' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function guaranteed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pembiayaan Berpenjamin',
            'slug' => 'pembiayaan-berpenjamin',
            'type' => FinancingCategoryType::Guaranteed->value,
        ]);
    }

    public function nonGuaranteed(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Pembiayaan Tanpa Penjamin',
            'slug' => 'pembiayaan-tanpa-penjamin',
            'type' => FinancingCategoryType::NonGuaranteed->value,
        ]);
    }
}