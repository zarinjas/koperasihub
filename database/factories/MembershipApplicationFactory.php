<?php

namespace Database\Factories;

use App\Enums\MembershipApplicationStatus;
use App\Models\Cooperative;
use App\Models\MembershipApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipApplication>
 */
class MembershipApplicationFactory extends Factory
{
    protected $model = MembershipApplication::class;

    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'application_no' => 'APP-'.fake()->unique()->numerify('########'),
            'full_name' => fake()->name(),
            'identity_no' => fake()->numerify('###########'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('01########'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'address_line_1' => fake()->streetAddress(),
            'country' => 'Malaysia',
            'occupation' => fake()->jobTitle(),
            'employer_name' => fake()->company(),
            'status' => MembershipApplicationStatus::Pending->value,
            'submitted_at' => now(),
            'metadata' => [
                'membership_type' => 'Individu',
                'notes' => fake()->sentence(),
            ],
        ];
    }

    public function underReview(): static
    {
        return $this->state(fn () => [
            'status' => MembershipApplicationStatus::UnderReview->value,
            'reviewed_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => MembershipApplicationStatus::Approved->value,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => MembershipApplicationStatus::Rejected->value,
            'reviewed_at' => now(),
            'rejection_reason' => 'Dokumen sokongan tidak lengkap.',
        ]);
    }
}
