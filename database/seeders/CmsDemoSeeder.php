<?php

namespace Database\Seeders;

use App\Enums\PageSectionType;
use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Cooperative;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class CmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->firstOrFail();
        $authorId = User::query()->where('email', 'cms@koperasihub.test')->value('id')
            ?? User::query()->where('email', 'admin@koperasihub.test')->value('id');

        $homepage = Page::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'slug' => 'home',
        ], [
            'title' => 'Utama',
            'template' => PageTemplate::Homepage->value,
            'summary' => 'Laman utama demo untuk kandungan CMS koperasi.',
            'status' => PageStatus::Published->value,
            'meta_title' => 'Koperasi Demo Berhad',
            'meta_description' => 'Akses maklumat keanggotaan, perkhidmatan, pengumuman dan borang koperasi dalam satu laman rasmi demo.',
            'featured_image_path' => null,
            'published_at' => now(),
            'created_by' => $authorId,
            'updated_by' => $authorId,
        ]);

        $sections = [
            [
                'type' => PageSectionType::Hero->value,
                'name' => 'Hero Utama',
                'data' => [
                    'badge' => 'Koperasi Demo Berhad',
                    'title' => 'Koperasi moden untuk keperluan anggota',
                    'subtitle' => 'Akses maklumat keanggotaan, perkhidmatan, pengumuman dan borang koperasi melalui satu laman rasmi yang mudah digunakan.',
                    'primary_button_text' => 'Daftar Anggota',
                    'primary_button_url' => '/member/register',
                    'secondary_button_text' => 'Lihat Perkhidmatan',
                    'secondary_button_url' => '/perkhidmatan',
                    'image_id' => null,
                ],
                'settings' => [
                    'variant' => 'image_right',
                    'background' => 'default',
                    'spacing' => 'xl',
                    'alignment' => 'left',
                    'container' => 'default',
                ],
            ],
            [
                'type' => PageSectionType::Stats->value,
                'name' => 'Statistik Ringkas',
                'data' => [
                    'items' => [
                        ['label' => 'Perkhidmatan demo', 'value' => '10+'],
                        ['label' => 'Kategori perniagaan', 'value' => '5'],
                        ['label' => 'Akses maklumat online', 'value' => '24/7'],
                    ],
                ],
                'settings' => [
                    'variant' => 'cards',
                    'background' => 'muted',
                    'spacing' => 'lg',
                    'alignment' => 'center',
                    'container' => 'default',
                    'columns' => 3,
                ],
            ],
            [
                'type' => PageSectionType::FeatureGrid->value,
                'name' => 'Ciri Utama',
                'data' => [
                    'title' => 'Urusan koperasi lebih mudah',
                    'subtitle' => 'Maklumat penting disusun supaya anggota dan pelawat boleh mendapatkan bantuan dengan cepat.',
                    'items' => [
                        ['title' => 'Permohonan keanggotaan online'],
                        ['title' => 'Semakan status permohonan'],
                        ['title' => 'Pengumuman rasmi koperasi'],
                        ['title' => 'Borang dan dokumen muat turun'],
                        ['title' => 'Direktori perkhidmatan anggota'],
                        ['title' => 'Saluran pertanyaan dan maklum balas'],
                    ],
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'default',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
            [
                'type' => PageSectionType::ServiceGrid->value,
                'name' => 'Perkhidmatan Anggota',
                'data' => [
                    'title' => 'Perkhidmatan anggota',
                    'subtitle' => 'Pilih perkhidmatan yang berkaitan dengan keperluan anda.',
                    'source' => 'services',
                    'limit' => 6,
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'muted',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
            [
                'type' => PageSectionType::BusinessUnits->value,
                'name' => 'Unit Perniagaan',
                'data' => [
                    'title' => 'Perniagaan dan kemudahan koperasi',
                    'subtitle' => 'Koperasi boleh memaparkan unit perniagaan, kemudahan dan aktiviti ekonomi yang tersedia.',
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'default',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
            [
                'type' => PageSectionType::AnnouncementList->value,
                'name' => 'Pengumuman Terkini',
                'data' => [
                    'title' => 'Pengumuman terkini',
                    'subtitle' => 'Ikuti hebahan rasmi, tarikh penting dan makluman perkhidmatan koperasi.',
                    'source' => 'latest',
                    'limit' => 3,
                    'button_text' => 'Lihat Semua Pengumuman',
                    'button_url' => '/pengumuman',
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'muted',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
            [
                'type' => PageSectionType::DownloadList->value,
                'name' => 'Dokumen Muat Turun',
                'data' => [
                    'title' => 'Borang dan dokumen',
                    'subtitle' => 'Muat turun borang umum dan dokumen rujukan koperasi.',
                    'source' => 'documents',
                    'category' => 'forms',
                    'limit' => 6,
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'default',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
            [
                'type' => PageSectionType::CtaBanner->value,
                'name' => 'Seruan Tindakan',
                'data' => [
                    'title' => 'Berminat menjadi anggota koperasi?',
                    'subtitle' => 'Hantar permohonan awal secara online dan pihak koperasi akan menyemak maklumat anda.',
                    'primary_button_text' => 'Daftar Sekarang',
                    'primary_button_url' => '/member/register',
                    'secondary_button_text' => 'Semak Permohonan',
                    'secondary_button_url' => '/semak-permohonan',
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'primary',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
            [
                'type' => PageSectionType::Faq->value,
                'name' => 'Soalan Lazim',
                'data' => [
                    'title' => 'Soalan lazim',
                    'subtitle' => 'Jawapan ringkas kepada pertanyaan umum berkaitan keanggotaan dan perkhidmatan koperasi.',
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'muted',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
            [
                'type' => PageSectionType::ContactBlock->value,
                'name' => 'Hubungi Kami',
                'data' => [
                    'title' => 'Hubungi kami',
                    'subtitle' => 'Ada pertanyaan? Sila hubungi koperasi melalui saluran rasmi yang dipaparkan.',
                    'show_contact_form' => true,
                ],
                'settings' => [
                    'variant' => 'default',
                    'background' => 'default',
                    'spacing' => 'lg',
                    'alignment' => 'left',
                    'container' => 'wide',
                ],
            ],
        ];

        foreach ($sections as $index => $section) {
            $homepage->sections()->updateOrCreate([
                'cooperative_id' => $cooperative->id,
                'type' => $section['type'],
                'sort_order' => $index + 1,
            ], [
                ...$section,
                'cooperative_id' => $cooperative->id,
                'created_by' => $authorId,
                'updated_by' => $authorId,
                'is_active' => true,
            ]);
        }
    }
}
