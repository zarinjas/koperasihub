<?php

namespace App\Services\Files;

use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MemberPhotoStorageService
{
    private const DIRECTORY = 'member-photos';

    public function store(UploadedFile $file, Member $member): string
    {
        $this->deleteOldFile($member->profile_photo_path);

        $path = $file->store(self::DIRECTORY, 'public');

        $member->forceFill(['profile_photo_path' => $path])->save();

        return $path;
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $timestamp = file_exists(storage_path('app/public/'.$path))
            ? '?v='.filemtime(storage_path('app/public/'.$path))
            : '';

        return '/storage/'.ltrim($path, '/').$timestamp;
    }

    private function deleteOldFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}