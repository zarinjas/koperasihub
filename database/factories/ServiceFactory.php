<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Cooperative;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(2);

        return [
            'cooperative_id' => Cooperative::factory(),
            'title' => $title,
            'slug' => $title,
            'category' => fake()->randomElement(['membership', 'financing', 'retail', 'property', 'community']),
            'summary' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'image_path' => null,
            'icon' => fake()->randomElement(['BriefcaseBusiness', 'Users', 'ShieldCheck', 'Store', 'Building2']),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_email' => fake()->safeEmail(),
            'whatsapp' => '+6012-0000000',
            'button_text' => 'Lihat Perincian',
            'button_url' => null,
            'status' => ServiceStatus::Draft->value,
            'sort_order' => 0,
            'is_featured' => false,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ServiceStatus::Published->value,
        ]);
    }
}
