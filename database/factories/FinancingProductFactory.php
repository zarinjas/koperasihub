<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingProductFactory extends Factory
{
    protected $model = FinancingProduct::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Pembiayaan Peribadi Berpenjamin',
            'Pembiayaan Pendidikan Berpenjamin',
            'Pembiayaan Kecil Tanpa Penjamin',
        ]);

        return [
            'cooperative_id' => Cooperative::factory(),
            'financing_category_id' => FinancingCategory::factory(),
            'unit_id' => Unit::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'description' => fake()->sentence(),
            'min_amount' => 1000,
            'max_amount' => 15000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 60,
            'requires_guarantor' => true,
            'guarantor_count' => 2,
            'required_documents_json' => ['Salinan IC', 'Slip gaji terkini'],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 30),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
