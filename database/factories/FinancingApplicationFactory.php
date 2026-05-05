<?php

namespace Database\Factories;

use App\Enums\FinancingApplicationStatus;
use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\Member;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingApplicationFactory extends Factory
{
    protected $model = FinancingApplication::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'unit_id' => Unit::factory(),
            'reference_no' => 'FIN-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'member_id' => Member::factory(),
            'financing_category_id' => FinancingCategory::factory(),
            'financing_product_id' => FinancingProduct::factory(),
            'amount_requested' => fake()->randomElement([3000, 5000, 12000]),
            'tenure_months' => fake()->randomElement([12, 24, 36]),
            'purpose' => fake()->sentence(8),
            'monthly_income' => fake()->randomFloat(2, 1800, 8000),
            'monthly_commitment' => fake()->randomFloat(2, 100, 2500),
            'employment_notes' => fake()->sentence(),
            'completed_form_pdf_path' => null,
            'completed_form_original_name' => null,
            'completed_form_uploaded_at' => null,
            'status' => FinancingApplicationStatus::PendingCompletedForm->value,
            'submitted_at' => now()->subDays(fake()->numberBetween(1, 10)),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'approved_amount' => null,
            'approved_tenure_months' => null,
            'decision_notes' => null,
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }
}
