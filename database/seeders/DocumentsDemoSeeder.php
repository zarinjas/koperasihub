<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\DocumentVisibility;
use App\Models\Cooperative;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();
        $admin = User::query()->where('email', 'admin@koperasihub.test')->first()
            ?? User::query()->where('email', 'superadmin@koperasihub.test')->first();

        if (! $cooperative || ! $admin) {
            return;
        }

        $categories = collect([
            ['name' => 'Borang', 'slug' => 'forms', 'description' => 'Borang umum koperasi.'],
            ['name' => 'Polisi', 'slug' => 'policies', 'description' => 'Dasar dan garis panduan.'],
            ['name' => 'Dalaman', 'slug' => 'internal', 'description' => 'Dokumen untuk rujukan pentadbir.'],
        ])->mapWithKeys(function (array $category) use ($cooperative) {
            $record = DocumentCategory::query()->updateOrCreate(
                ['cooperative_id' => $cooperative->id, 'slug' => $category['slug']],
                [...$category, 'cooperative_id' => $cooperative->id, 'is_active' => true],
            );

            return [$category['slug'] => $record];
        });

        $this->ensureDemoFile('documents/demo-borang-keanggotaan.pdf', 'Borang permohonan keanggotaan demo.');
        $this->ensureDemoFile('documents/demo-kemaskini-ahli.pdf', 'Borang kemas kini maklumat ahli demo.');
        $this->ensureDemoFile('documents/demo-garis-panduan-admin.pdf', 'Panduan pentadbiran dalaman demo.');
        $this->ensureDemoFile('documents/demo-panduan-portal-ahli.pdf', 'Panduan ringkas penggunaan portal ahli demo.');
        $this->ensureDemoFile('documents/demo-polisi-privasi.pdf', 'Polisi privasi dan perlindungan data demo.');

        $documents = [
            [
                'title' => 'Borang Permohonan Keanggotaan',
                'slug' => 'borang-permohonan-keanggotaan',
                'description' => 'Borang asas untuk permohonan anggota baharu.',
                'file_path' => 'documents/demo-borang-keanggotaan.pdf',
                'file_name' => 'borang-permohonan-keanggotaan.pdf',
                'visibility' => DocumentVisibility::Public->value,
                'status' => DocumentStatus::Published->value,
                'document_category_id' => $categories['forms']->id,
            ],
            [
                'title' => 'Borang Kemas Kini Maklumat Ahli',
                'slug' => 'borang-kemaskini-maklumat-ahli',
                'description' => 'Gunakan borang ini untuk mengemas kini butiran ahli.',
                'file_path' => 'documents/demo-kemaskini-ahli.pdf',
                'file_name' => 'borang-kemaskini-ahli.pdf',
                'visibility' => DocumentVisibility::Public->value,
                'status' => DocumentStatus::Published->value,
                'document_category_id' => $categories['forms']->id,
            ],
            [
                'title' => 'Garis Panduan Admin Dalaman',
                'slug' => 'garis-panduan-admin-dalaman',
                'description' => 'Dokumen rujukan dalaman untuk pentadbir koperasi.',
                'file_path' => 'documents/demo-garis-panduan-admin.pdf',
                'file_name' => 'garis-panduan-admin.pdf',
                'visibility' => DocumentVisibility::AdminOnly->value,
                'status' => DocumentStatus::Published->value,
                'document_category_id' => $categories['internal']->id,
            ],
            [
                'title' => 'Panduan Ringkas Portal Ahli',
                'slug' => 'panduan-ringkas-portal-ahli',
                'description' => 'Panduan asas log masuk dan penggunaan menu utama portal ahli.',
                'file_path' => 'documents/demo-panduan-portal-ahli.pdf',
                'file_name' => 'panduan-portal-ahli.pdf',
                'visibility' => DocumentVisibility::MembersOnly->value,
                'status' => DocumentStatus::Published->value,
                'document_category_id' => $categories['forms']->id,
            ],
            [
                'title' => 'Polisi Privasi Demo',
                'slug' => 'polisi-privasi-demo',
                'description' => 'Rujukan ringkas mengenai perlindungan data dan penggunaan maklumat.',
                'file_path' => 'documents/demo-polisi-privasi.pdf',
                'file_name' => 'polisi-privasi-demo.pdf',
                'visibility' => DocumentVisibility::Public->value,
                'status' => DocumentStatus::Published->value,
                'document_category_id' => $categories['policies']->id,
            ],
        ];

        foreach ($documents as $document) {
            Document::query()->updateOrCreate(
                ['cooperative_id' => $cooperative->id, 'slug' => $document['slug']],
                [
                    ...$document,
                    'cooperative_id' => $cooperative->id,
                    'uploaded_by' => $admin->id,
                    'mime_type' => 'application/pdf',
                    'file_size' => Storage::disk('local')->size($document['file_path']),
                    'published_at' => now()->subDay(),
                ],
            );
        }
    }

    private function ensureDemoFile(string $path, string $contents): void
    {
        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, $contents);
        }
    }
}