<?php

namespace Database\Factories;

use App\Enums\PageSectionType;
use App\Models\Cooperative;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageSection>
 */
class PageSectionFactory extends Factory
{
    public function definition(): array
    {
        $cooperative = Cooperative::factory();

        return [
            'cooperative_id' => $cooperative,
            'page_id' => Page::factory()->state(fn () => [
                'cooperative_id' => $cooperative,
            ]),
            'type' => PageSectionType::Hero->value,
            'name' => 'Hero Utama',
            'data' => [
                'title' => 'Tajuk demo',
                'subtitle' => 'Penerangan demo',
            ],
            'settings' => [
                'variant' => 'default',
                'background' => 'default',
                'spacing' => 'md',
                'alignment' => 'left',
                'container' => 'default',
            ],
            'sort_order' => 1,
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
