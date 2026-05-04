<?php

namespace Database\Seeders;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->firstOrFail();
        $authorId = User::query()->where('email', 'admin@koperasihub.test')->value('id')
            ?? User::query()->where('email', 'superadmin@koperasihub.test')->value('id');

        $items = [
            [
                'title' => 'Pembukaan Permohonan Keanggotaan Sesi Demo',
                'summary' => 'Permohonan keanggotaan baharu kini boleh dibuat melalui borang online.',
                'days_ago' => 2,
                'is_pinned' => true,
            ],
            [
                'title' => 'Notis Kemaskini Maklumat Anggota',
                'summary' => 'Anggota digalakkan menyemak dan mengemaskini maklumat peribadi melalui portal ahli.',
                'days_ago' => 5,
                'is_pinned' => false,
            ],
            [
                'title' => 'Hebahan Muat Turun Borang Terkini',
                'summary' => 'Borang perkhidmatan koperasi telah dikemaskini untuk rujukan anggota.',
                'days_ago' => 8,
                'is_pinned' => false,
            ],
            [
                'title' => 'Makluman Waktu Operasi Kaunter',
                'summary' => 'Sila rujuk waktu operasi kaunter sebelum hadir untuk urusan fizikal.',
                'days_ago' => 12,
                'is_pinned' => false,
            ],
            [
                'title' => 'Panduan Akses Portal Ahli',
                'summary' => 'Panduan ringkas log masuk dan penggunaan modul utama portal ahli kini tersedia untuk rujukan.',
                'days_ago' => 1,
                'is_pinned' => true,
            ],
        ];

        foreach ($items as $item) {
            $publishedAt = now()->subDays($item['days_ago']);

            Announcement::query()->updateOrCreate([
                'cooperative_id' => $cooperative->id,
                'slug' => $item['title'],
            ], [
                'title' => $item['title'],
                'summary' => $item['summary'],
                'content' => $item['summary'].' Maklumat ini disediakan sebagai kandungan demo dan boleh dikemaskini oleh pihak admin mengikut keperluan koperasi.',
                'audience' => AnnouncementAudience::Public->value,
                'status' => AnnouncementStatus::Published->value,
                'is_pinned' => $item['is_pinned'],
                'published_at' => $publishedAt,
                'expires_at' => null,
                'created_by' => $authorId,
                'updated_by' => $authorId,
            ]);
        }
    }
}
