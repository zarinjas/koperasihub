<?php

namespace Database\Factories;

use App\Enums\MediaVisibility;
use App\Models\Cooperative;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cooperative_id' => Cooperative::factory(),
            'uploaded_by' => User::factory(),
            'disk' => 'public',
            'path' => 'media/demo-image.jpg',
            'original_name' => 'demo-image.jpg',
            'file_name' => 'demo-image.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 240000,
            'visibility' => MediaVisibility::Public->value,
            'collection' => 'general',
            'alt_text' => 'Imej demo',
            'caption' => null,
            'metadata' => null,
        ];
    }
}
