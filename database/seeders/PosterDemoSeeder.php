<?php

namespace Database\Seeders;

use App\Enums\PosterStatus;
use App\Models\Cooperative;
use App\Models\Poster;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PosterDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $superAdmin = User::query()->where('email', 'superadmin@koperasihub.test')->first();

        $posters = [
            [
                'title' => 'Infografik Simpanan Anggota',
                'alt_text' => 'Infografik tentang manfaat simpanan anggota koperasi',
                'color' => [15, 118, 110],
            ],
            [
                'title' => 'Poster Pembiayaan Pendidikan',
                'alt_text' => 'Poster promosi skim pembiayaan pendidikan',
                'color' => [29, 78, 216],
            ],
            [
                'title' => 'Infografik Perkhidmatan Koperasi',
                'alt_text' => 'Infografik ringkas perkhidmatan utama koperasi',
                'color' => [22, 163, 74],
            ],
            [
                'title' => 'Poster Program Literasi Kewangan',
                'alt_text' => 'Poster program literasi kewangan untuk anggota',
                'color' => [217, 119, 6],
            ],
            [
                'title' => 'Infografik Mesyuarat Agung Tahunan',
                'alt_text' => 'Infografik tarikh penting mesyuarat agung tahunan',
                'color' => [220, 38, 38],
            ],
            [
                'title' => 'Promosi Kedai Koperasi',
                'alt_text' => 'Poster promosi produk kedai koperasi',
                'color' => [124, 58, 237],
            ],
        ];

        Storage::disk('public')->makeDirectory('posters/demo');

        foreach ($posters as $posterData) {
            $filename = 'posters/demo/poster-'.str($posterData['title'])->slug().'.jpg';
            $this->generatePlaceholderImage(
                storage_path('app/public/'.$filename),
                1080,
                1350,
                $posterData['color']
            );

            Poster::query()->create([
                'cooperative_id' => $cooperative->id,
                'title' => $posterData['title'],
                'image_path' => $filename,
                'alt_text' => $posterData['alt_text'],
                'status' => PosterStatus::Published->value,
                'is_active' => true,
                'published_at' => now(),
                'created_by' => $superAdmin?->id,
                'updated_by' => $superAdmin?->id,
            ]);
        }
    }

    private function generatePlaceholderImage(string $path, int $width, int $height, array $rgb): void
    {
        $image = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefill($image, 0, 0, $bg);

        $white = imagecolorallocate($image, 255, 255, 255);
        $light = imagecolorallocatealpha($image, 255, 255, 255, 60);

        imagefilledrectangle($image, 0, 0, $width, 8, $light);
        imagefilledrectangle($image, 0, $height - 8, $width, $height, $light);

        $label = pathinfo($path, PATHINFO_FILENAME);
        $label = str_replace('poster-', '', $label);
        $label = str_replace('-', ' ', $label);
        $label = ucwords($label);

        $fontPath = null;
        foreach (['/System/Library/Fonts/Helvetica.ttc', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', '/usr/share/fonts/TTF/DejaVuSans.ttf'] as $f) {
            if (file_exists($f)) {
                $fontPath = $f;
                break;
            }
        }

        if ($fontPath) {
            $fontSize = 32;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $label);
            $tw = $bbox[2] - $bbox[0];
            $th = $bbox[1] - $bbox[7];
            $x = ($width - $tw) / 2;
            $y = ($height / 2) + ($th / 2);
            imagettftext($image, $fontSize, 0, (int) $x, (int) $y, $white, $fontPath, $label);
        } else {
            $fontSize = 5;
            $tw = imagefontwidth($fontSize) * strlen($label);
            $x = ($width - $tw) / 2;
            $y = ($height - imagefontheight($fontSize)) / 2;
            imagestring($image, $fontSize, (int) $x, (int) $y, $label, $white);
        }

        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }
}