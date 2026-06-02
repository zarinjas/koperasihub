<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingApplicationDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingApplicationDocumentFactory extends Factory
{
    protected $model = FinancingApplicationDocument::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'financing_application_id' => FinancingApplication::factory(),
            'uploaded_by' => User::factory(),
            'label' => fake()->words(2, true),
            'field_key' => 'doc_'.fake()->randomNumber(4),
            'file_path' => 'financing/documents/dummy.pdf',
            'original_name' => 'dokumen.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10000, 500000),
        ];
    }
}