<?php

namespace Database\Factories;

use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Models\Cooperative;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'cooperative_id' => Cooperative::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['agm', 'seminar', 'kursus', 'webinar', 'community', 'volunteer']),
            'program_type' => fake()->randomElement(ProgramType::values()),
            'location' => fake()->optional()->address(),
            'online_url' => fake()->optional()->url(),
            'capacity' => fake()->optional()->numberBetween(20, 500),
            'start_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'end_date' => fake()->optional()->dateTimeBetween('+1 week', '+3 months'),
            'registration_deadline' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'cover_image_path' => null,
            'status' => ProgramStatus::Published->value,
            'is_featured' => fake()->boolean(20),
            'sort_order' => 0,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::Draft->value,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::Published->value,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::Cancelled->value,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::Completed->value,
            'start_date' => fake()->dateTimeBetween('-3 months', '-1 week'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProgramStatus::Completed->value,
            'start_date' => fake()->dateTimeBetween('-3 months', '-1 week'),
        ]);
    }
}
