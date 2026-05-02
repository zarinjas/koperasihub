<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Cooperative;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'cooperative_id' => Cooperative::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'template' => PageTemplate::Default->value,
            'summary' => fake()->paragraph(),
            'status' => PageStatus::Draft->value,
            'meta_title' => $title,
            'meta_description' => fake()->sentence(12),
            'featured_image_path' => null,
            'published_at' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published->value,
            'published_at' => now()->subMinute(),
        ]);
    }

    public function homepage(): static
    {
        return $this->state(fn () => [
            'title' => 'Utama',
            'slug' => 'home',
            'template' => PageTemplate::Homepage->value,
        ]);
    }
}
