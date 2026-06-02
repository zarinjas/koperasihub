<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\Popup;
use App\Models\User;
use Illuminate\Database\Seeder;

class PopupDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $superAdmin = User::query()->where('email', 'superadmin@koperasihub.test')->first();

        Popup::query()->create([
            'cooperative_id' => $cooperative->id,
            'title' => 'Promosi Ahli Baru 2026',
            'content' => "Yuran pendaftaran PERCUMA untuk ahli baru sehingga 31 Disember 2026!\n\nJangan lepaskan peluang menjadi sebahagian daripada keluarga koperasi kami. Pelbagai manfaat dan dividen menarik menanti anda.",
            'button_text' => 'Daftar Sekarang',
            'button_url' => 'https://koperasihub.test/daftar',
            'is_active' => true,
            'starts_at' => now(),
            'ends_at' => now()->addMonths(3),
            'created_by' => $superAdmin?->id,
            'updated_by' => $superAdmin?->id,
        ]);

        Popup::query()->create([
            'cooperative_id' => $cooperative->id,
            'title' => 'Mesyuarat Agung Tahunan',
            'content' => "Mesyuarat Agung Tahunan (MAT) Koperasi Demo Berhad akan diadakan pada:\n\nTarikh: 15 Julai 2026 (Rabu)\nMasa: 9:00 pagi - 1:00 petang\nTempat: Dewan Serbaguna Koperasi\n\nKehadiran semua ahli adalah diwajibkan.",
            'button_text' => 'Lihat Agenda',
            'button_url' => 'https://koperasihub.test/agenda',
            'is_active' => true,
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(45),
            'created_by' => $superAdmin?->id,
            'updated_by' => $superAdmin?->id,
        ]);

        Popup::query()->create([
            'cooperative_id' => $cooperative->id,
            'title' => 'Pembiayaan Pendidikan Khas',
            'content' => 'Dapatkan pembiayaan pendidikan dengan kadar serendah 3.5% setahun. Pinjaman sehingga RM50,000 untuk yuran pengajian, buku, dan peralatan pembelajaran.',
            'button_text' => 'Mohon Sekarang',
            'button_url' => 'https://koperasihub.test/pembiayaan/pendidikan',
            'is_active' => false,
            'starts_at' => now(),
            'ends_at' => now()->addMonths(6),
            'created_by' => $superAdmin?->id,
            'updated_by' => $superAdmin?->id,
        ]);
    }
}
