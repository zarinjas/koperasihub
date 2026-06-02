<?php

namespace Database\Factories;

use App\Models\FormSection;
use App\Models\OnlineForm;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormSectionFactory extends Factory
{
    protected $model = FormSection::class;

    public function definition(): array
    {
        return [
            'online_form_id' => OnlineForm::factory(),
            'title' => fake()->randomElement([
                'Maklumat Peribadi',
                'Maklumat Pekerjaan',
                'Maklumat Waris',
                'Pengesahan',
            ]),
            'description' => 'Seksyen borang rasmi.',
            'page_break_before' => false,
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}