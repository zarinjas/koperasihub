<?php

namespace App\Services\Files;

use App\Models\Cooperative;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingStorageService
{
    private const LOGO_DIR = 'branding/logos';
    private const FAVICON_DIR = 'branding/favicons';

    public function storeLogo(UploadedFile $file, Cooperative $cooperative): string
    {
        $this->deleteOldFile($cooperative->logo_path);

        $path = $file->store(self::LOGO_DIR, 'public');

        $cooperative->forceFill(['logo_path' => $path])->save();

        return $path;
    }

    public function storeFavicon(UploadedFile $file, Cooperative $cooperative): string
    {
        $this->deleteOldFile($cooperative->favicon_path);

        $path = $file->store(self::FAVICON_DIR, 'public');

        $cooperative->forceFill(['favicon_path' => $path])->save();

        return $path;
    }

    public function logoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function faviconUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function deleteOldFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
