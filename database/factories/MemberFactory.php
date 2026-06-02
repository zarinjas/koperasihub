<?php

namespace Database\Factories;

use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'user_id' => null,
            'member_no' => 'MBR-'.now()->format('Ymd').'-'.fake()->unique()->bothify('??##??'),
            'profile_photo_path' => null,
            'card_public_token' => fake()->unique()->regexify('[A-Za-z0-9]{48}'),
            'card_token_generated_at' => now(),
            'full_name' => fake()->name(),
            'identity_no' => fake()->unique()->numerify('############'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('01########'),
            'address_line_1' => fake()->address(),
            'country' => 'Malaysia',
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female']),
            'position' => fake()->jobTitle(),
            'department' => fake()->word(),
            'employer' => fake()->company(),
            'monthly_fee' => fake()->optional(0.7)->randomFloat(2, 50, 500),
            'total_fee' => fake()->optional(0.5)->randomFloat(2, 500, 10000),
            'special_savings' => fake()->optional(0.5)->randomFloat(2, 1000, 50000),
            'monthly_deduction' => fake()->optional(0.7)->randomFloat(2, 50, 500),
            'total_debt' => fake()->optional(0.3)->randomFloat(2, 0, 50000),
            'membership_status' => MemberStatus::Active->value,
'next_of_kin_name' => fake()->optional()->name(),
'next_of_kin_relation' => fake()->optional()->randomElement(['Anak kandung', 'Anak tiri', 'Adik beradik', 'Lain-lain']),
'next_of_kin_phone' => fake()->optional()->numerify('01########'),
            'next_of_kin_address' => fake()->optional()->address(),
            'spouse_name' => fake()->optional()->name(),
            'spouse_phone' => fake()->optional()->numerify('01########'),
            'spouse_address' => fake()->optional()->address(),
            'joined_at' => now()->subMonths(rand(1, 12)),
            'approved_at' => now()->subMonths(rand(1, 12)),
            'approved_by' => null,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['membership_status' => MemberStatus::Active->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['membership_status' => MemberStatus::Inactive->value]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['membership_status' => MemberStatus::Suspended->value]);
    }
}