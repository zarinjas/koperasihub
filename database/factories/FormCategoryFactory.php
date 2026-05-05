<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\FormCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormCategoryFactory extends Factory
{
    protected $model = FormCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Keanggotaan',
            'Pembiayaan',
            'Takaful',
            'Bilik Seminar',
            'Kebajikan Anggota',
        ]);

        return [
            'cooperative_id' => Cooperative::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => 'Kategori borang untuk urusan '.$name.'.',
            'icon' => 'FileText',
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
