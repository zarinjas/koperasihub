<?php

namespace App\Services\Files;

use App\Enums\MediaVisibility;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class MediaFileService
{
    public function store(UploadedFile $file, User $user, array $attributes = []): MediaFile
    {
        $path = $file->store('media', 'public');

        return MediaFile::query()->create([
            'cooperative_id' => $user->cooperative_id,
            'uploaded_by' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => basename($path),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'visibility' => $attributes['visibility'] ?? MediaVisibility::Public->value,
            'collection' => $attributes['collection'] ?? null,
            'alt_text' => $attributes['alt_text'] ?? null,
            'caption' => $attributes['caption'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }
}
