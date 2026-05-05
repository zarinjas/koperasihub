<?php

namespace Database\Factories;

use App\Enums\FormStatus;
use App\Enums\FormVisibility;
use App\Models\Cooperative;
use App\Models\FormCategory;
use App\Models\OnlineForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OnlineFormFactory extends Factory
{
    protected $model = OnlineForm::class;

    public function definition(): array
    {
        $title = 'Borang '.fake()->unique()->sentence(3);

        return [
            'cooperative_id' => Cooperative::factory(),
            'form_category_id' => FormCategory::factory(),
            'created_by' => User::factory()->admin(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => 'Borang rasmi untuk urusan koperasi.',
            'visibility' => FormVisibility::Public->value,
            'status' => FormStatus::Draft->value,
            'success_message' => 'Borang anda berjaya dihantar.',
            'document_code' => 'FRM/'.fake()->numerify('###'),
            'revision_no' => '01',
            'effective_date' => now()->toDateString(),
            'document_title' => $title,
            'show_document_header' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => FormStatus::Published->value,
        ]);
    }

    public function membersOnly(): static
    {
        return $this->state(fn () => [
            'visibility' => FormVisibility::MembersOnly->value,
        ]);
    }
}
