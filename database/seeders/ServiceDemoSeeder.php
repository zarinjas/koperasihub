<?php

namespace Database\Seeders;

use App\Enums\ServiceStatus;
use App\Models\Cooperative;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->firstOrFail();
        $authorId = User::query()->where('email', 'admin@koperasihub.test')->value('id')
            ?? User::query()->where('email', 'superadmin@koperasihub.test')->value('id');

        $services = [
            ['title' => 'Keanggotaan', 'category' => 'membership', 'icon' => 'Users', 'sort_order' => 1],
            ['title' => 'Pembiayaan Anggota', 'category' => 'financing', 'icon' => 'WalletCards', 'sort_order' => 2],
            ['title' => 'Simpanan & Syer', 'category' => 'membership', 'icon' => 'PiggyBank', 'sort_order' => 3],
            ['title' => 'Takaful Kenderaan', 'category' => 'insurance', 'icon' => 'ShieldCheck', 'sort_order' => 4],
            ['title' => 'Ar-Rahnu', 'category' => 'financing', 'icon' => 'BadgeDollarSign', 'sort_order' => 5],
            ['title' => 'Kedai Koperasi', 'category' => 'retail', 'icon' => 'Store', 'sort_order' => 6],
            ['title' => 'Hartanah & Sewaan', 'category' => 'property', 'icon' => 'Building2', 'sort_order' => 7],
            ['title' => 'Stesen Minyak', 'category' => 'retail', 'icon' => 'Fuel', 'sort_order' => 8],
            ['title' => 'E-Dagang', 'category' => 'community', 'icon' => 'ShoppingCart', 'sort_order' => 9],
            ['title' => 'Bilik Seminar', 'category' => 'education', 'icon' => 'Presentation', 'sort_order' => 10],
            ['title' => 'Kebajikan Anggota', 'category' => 'community', 'icon' => 'HeartHandshake', 'sort_order' => 11],
        ];

        $copy = [
            'Keanggotaan' => 'Maklumat syarat keahlian, proses permohonan dan kemas kini status anggota.',
            'Pembiayaan Anggota' => 'Panduan permohonan pembiayaan anggota tertakluk kepada syarat koperasi.',
            'Simpanan & Syer' => 'Maklumat berkaitan caruman syer, simpanan anggota dan rekod pegangan.',
            'Takaful Kenderaan' => 'Rujukan permohonan dan pembaharuan perlindungan takaful kenderaan.',
            'Ar-Rahnu' => 'Maklumat umum pajak gadai Islam yang boleh ditawarkan oleh koperasi.',
            'Kedai Koperasi' => 'Jualan barangan keperluan harian, produk anggota atau barangan terpilih.',
            'Hartanah & Sewaan' => 'Maklumat premis, ruang niaga atau aset sewaan koperasi.',
            'Stesen Minyak' => 'Paparan unit perniagaan stesen minyak sebagai contoh aktiviti ekonomi koperasi.',
            'E-Dagang' => 'Saluran jualan online untuk produk koperasi atau produk anggota.',
            'Bilik Seminar' => 'Maklumat kemudahan bilik mesyuarat, latihan atau seminar untuk tempahan.',
            'Kebajikan Anggota' => 'Bantuan, sumbangan atau manfaat kebajikan mengikut polisi koperasi.',
        ];

        foreach ($services as $service) {
            $title = $service['title'];

            Service::query()->updateOrCreate([
                'cooperative_id' => $cooperative->id,
                'slug' => $title,
            ], [
                'title' => $title,
                'category' => $service['category'],
                'summary' => $copy[$title],
                'description' => $copy[$title].' Maklumat lanjut, syarat kelayakan, dan saluran tindakan boleh dikemas kini oleh pihak admin mengikut keperluan koperasi.',
                'icon' => $service['icon'],
                'status' => ServiceStatus::Published->value,
                'sort_order' => $service['sort_order'],
                'is_featured' => $service['sort_order'] <= 6,
                'button_text' => 'Hubungi Admin',
                'button_url' => '/hubungi',
                'contact_name' => 'Unit Perkhidmatan',
                'contact_phone' => '+603-0000 0000',
                'contact_email' => 'info@koperasidemo.test',
                'whatsapp' => '+6012-000 0000',
                'created_by' => $authorId,
                'updated_by' => $authorId,
            ]);
        }
    }
}