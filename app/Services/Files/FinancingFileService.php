<?php

namespace App\Services\Files;

use App\Models\FinancingDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FinancingFileService
{
    public function storeRateImage(UploadedFile $file): string
    {
        return $file->store('financing/rate-images', 'public');
    }

    public function deletePublicFile(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function storeSupportingDocument(UploadedFile $file): array
    {
        $path = $file->store('financing/documents', 'local');

        return [
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function storeProductPdf(UploadedFile $file): array
    {
        $path = $file->store('financing/product-documents', 'local');

        return [
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function storeCompletedFormPdf(UploadedFile $file): array
    {
        $path = $file->store('financing/completed-forms', 'local');

        return [
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function storeSignatureDataUrl(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/png;base64,(.+)$/', $dataUrl, $matches)) {
            throw new RuntimeException('Format tandatangan tidak sah.');
        }

        $binary = base64_decode($matches[1], true);

        if ($binary === false) {
            throw new RuntimeException('Format tandatangan tidak sah.');
        }

        $path = 'financing/signatures/'.now()->format('Y/m').'/'.Str::uuid().'.png';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    public function signatureDataUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
    }

    public function deletePrivateFile(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function downloadName(FinancingDocument $document): string
    {
        return $document->file_name ?: basename($document->file_path);
    }
}