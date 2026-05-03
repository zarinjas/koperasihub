<?php

namespace Database\Factories;

use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Cooperative;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'member_id' => Member::factory(),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'ticket_no' => 'ADU-'.fake()->unique()->numerify('########'),
            'category' => fake()->randomElement(['aduan', 'cadangan', 'portal', 'dokumen', 'keahlian']),
            'subject' => fake()->sentence(5),
            'message' => fake()->paragraphs(2, true),
            'status' => ComplaintStatus::Open->value,
            'priority' => ComplaintPriority::Medium->value,
            'closed_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => ComplaintStatus::Open->value,
            'closed_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => ComplaintStatus::InProgress->value,
            'closed_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => ComplaintStatus::Resolved->value,
            'closed_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => ComplaintStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }
}
