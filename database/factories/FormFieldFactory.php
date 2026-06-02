<?php

namespace Database\Factories;

use App\Enums\FormFieldType;
use App\Models\FormField;
use App\Models\FormSection;
use App\Models\OnlineForm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    public function definition(): array
    {
        $label = fake()->randomElement(['Nama penuh', 'No. telefon', 'Alamat', 'Pekerjaan']);

        return [
            'online_form_id' => OnlineForm::factory(),
            'form_section_id' => FormSection::factory(),
            'label' => $label,
            'field_key' => Str::snake($label),
            'type' => FormFieldType::ShortText->value,
            'placeholder' => null,
            'help_text' => null,
            'is_required' => true,
            'options_json' => null,
            'validation_json' => [],
            'settings_json' => ['display_mode' => 'online_and_print'],
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}