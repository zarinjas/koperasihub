<?php

namespace Database\Factories;

use App\Enums\NewsCategory;
use App\Enums\NewsStatus;
use App\Models\Cooperative;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'cooperative_id' => Cooperative::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(15),
            'content' => fake()->paragraphs(3, true),
            'image_path' => null,
            'category' => fake()->randomElement(NewsCategory::values()),
            'status' => NewsStatus::Draft->value,
            'published_at' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => NewsStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => NewsStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => NewsStatus::Archived->value,
        ]);
    }

    public function category(string $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}
