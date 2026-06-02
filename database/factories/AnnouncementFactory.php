<?php

namespace Database\Factories;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'cooperative_id' => Cooperative::factory(),
            'title' => $title,
            'slug' => $title,
            'summary' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'image_path' => null,
            'audience' => AnnouncementAudience::Public->value,
            'status' => AnnouncementStatus::Draft->value,
            'is_pinned' => false,
            'send_notification' => false,
            'send_email' => false,
            'published_at' => null,
            'expires_at' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => AnnouncementStatus::Published->value,
            'published_at' => now()->subHour(),
        ]);
    }

    public function public(): static
    {
        return $this->state(fn () => [
            'audience' => AnnouncementAudience::Public->value,
        ]);
    }

    public function membersOnly(): static
    {
        return $this->state(fn () => [
            'audience' => AnnouncementAudience::Members->value,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn () => [
            'is_pinned' => true,
        ]);
    }

    public function withNotification(): static
    {
        return $this->state(fn () => [
            'send_notification' => true,
        ]);
    }

    public function withEmail(): static
    {
        return $this->state(fn () => [
            'send_notification' => true,
            'send_email' => true,
        ]);
    }
}