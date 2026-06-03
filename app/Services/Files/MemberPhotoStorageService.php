<?php

namespace App\Services\Files;

use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MemberPhotoStorageService
{
    private const DIRECTORY = 'member-photos';

    public function store(UploadedFile $file, Member $member): string
    {
        $this->deleteOldFile($member->profile_photo_path);

        $path = $file->store(self::DIRECTORY, 'public');

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan foto profil. Sila cuba lagi.');
        }

        $member->forceFill(['profile_photo_path' => $path])->save();

        return $path;
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $url = Storage::disk('public')->url($path);
        $timestamp = '?v='.Storage::disk('public')->lastModified($path);

        return $url.$timestamp;
    }

    private function deleteOldFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}