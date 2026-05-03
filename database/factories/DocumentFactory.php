<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentVisibility;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'cooperative_id' => Cooperative::factory(),
            'document_category_id' => null,
            'member_id' => null,
            'uploaded_by' => User::factory(),
            'title' => $title,
            'slug' => $title,
            'description' => fake()->sentence(),
            'file_path' => 'documents/demo-file.pdf',
            'file_name' => 'demo-file.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 256000,
            'visibility' => DocumentVisibility::Public->value,
            'status' => DocumentStatus::Published->value,
            'version' => '1.0',
            'published_at' => now()->subDay(),
            'expires_at' => null,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => [
            'visibility' => DocumentVisibility::Public->value,
            'status' => DocumentStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
    }

    public function adminOnly(): static
    {
        return $this->state(fn () => [
            'visibility' => DocumentVisibility::AdminOnly->value,
        ]);
    }

    public function membersOnly(): static
    {
        return $this->state(fn () => [
            'visibility' => DocumentVisibility::MembersOnly->value,
        ]);
    }

    public function withCategory(DocumentCategory $category): static
    {
        return $this->state(fn () => [
            'document_category_id' => $category->id,
            'cooperative_id' => $category->cooperative_id,
        ]);
    }
}
