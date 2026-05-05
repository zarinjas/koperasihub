<?php

namespace Database\Factories;

use App\Enums\FinancingCategoryType;
use App\Models\Cooperative;
use App\Models\FinancingCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingCategoryFactory extends Factory
{
    protected $model = FinancingCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Pembiayaan Berpenjamin',
            'Pembiayaan Tanpa Penjamin',
            'Pembiayaan Khas Ahli',
        ]);

        return [
            'cooperative_id' => Cooperative::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(FinancingCategoryType::values()),
            'rate_image_path' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
