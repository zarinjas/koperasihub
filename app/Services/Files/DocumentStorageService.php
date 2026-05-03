<?php

namespace App\Services\Files;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    public function store(UploadedFile $file, User $user, array $attributes = []): Document
    {
        $path = $file->store('documents', 'local');

        return Document::query()->create([
            'cooperative_id' => $user->cooperative_id,
            'document_category_id' => $attributes['document_category_id'] ?? null,
            'member_id' => $attributes['member_id'] ?? null,
            'uploaded_by' => $user->id,
            'title' => $attributes['title'],
            'slug' => $attributes['slug'] ?? $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'file_path' => $path,
            'file_name' => $attributes['file_name'] ?? $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'visibility' => $attributes['visibility'],
            'status' => $attributes['status'],
            'version' => $attributes['version'] ?? null,
            'published_at' => $this->resolvePublishedAt($attributes),
            'expires_at' => $attributes['expires_at'] ?? null,
        ]);
    }

    public function replace(Document $document, UploadedFile $file): Document
    {
        Storage::disk('local')->delete($document->file_path);

        $path = $file->store('documents', 'local');

        $document->forceFill([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ])->save();

        return $document;
    }

    public function delete(Document $document): void
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();
    }

    private function resolvePublishedAt(array $attributes)
    {
        if (($attributes['status'] ?? null) !== DocumentStatus::Published->value) {
            return $attributes['published_at'] ?? null;
        }

        return $attributes['published_at'] ?? now();
    }
}
