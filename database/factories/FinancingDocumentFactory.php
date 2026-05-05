<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingDocumentFactory extends Factory
{
    protected $model = FinancingDocument::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'financing_application_id' => FinancingApplication::factory(),
            'uploaded_by' => User::factory(),
            'label' => 'Slip gaji terkini',
            'document_key' => 'slip-gaji-terkini',
            'file_path' => 'financing/documents/demo.pdf',
            'file_name' => 'demo.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
        ];
    }
}
