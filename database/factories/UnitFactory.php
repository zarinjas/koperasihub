<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'name' => $this->faker->unique()->company(),
            'slug' => fn (array $attributes) => Str::slug($attributes['name']),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}