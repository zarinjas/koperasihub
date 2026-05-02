<?php

namespace Database\Factories;

use App\Models\Cooperative;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cooperative>
 */
class CooperativeFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Koperasi '.fake()->unique()->company().' Berhad';

        return [
            'name' => $name,
            'short_name' => Str::of($name)->before(' Berhad')->value(),
            'registration_no' => 'D-'.fake()->numerify('####'),
            'slug' => Str::slug($name),
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => '#0F766E',
            'secondary_color' => '#1D4ED8',
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => fake()->city(),
            'state' => 'Wilayah Persekutuan Kuala Lumpur',
            'postcode' => fake()->postcode(),
            'country' => 'Malaysia',
            'phone' => '+603-0000 0000',
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => '+6012-000 0000',
            'website_url' => 'https://'.fake()->domainName(),
            'facebook_url' => null,
            'instagram_url' => null,
            'linkedin_url' => null,
            'footer_text' => 'Footer demo koperasi.',
            'status' => 'active',
        ];
    }
}
