<?php

namespace Database\Seeders;

use App\Enums\BannerStatus;
use App\Models\Banner;
use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BannerDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $superAdmin = User::query()->where('email', 'superadmin@koperasihub.test')->first();

        $banners = [
            [
                'title' => 'Promosi Pembiayaan Peribadi',
                'alt_text' => 'Banner promosi pembiayaan peribadi sehingga RM50,000',
                'link_url' => '/member/financing',
                'color' => [15, 118, 110],
            ],
            [
                'title' => 'Program Saham Anggota',
                'alt_text' => 'Banner program saham anggota koperasi',
                'link_url' => '/member/announcements',
                'color' => [29, 78, 216],
            ],
            [
                'title' => 'Kemas Kini Profil Ahli',
                'alt_text' => 'Banner kemas kini profil ahli untuk data terkini',
                'link_url' => '/member/profile',
                'color' => [124, 58, 237],
            ],
        ];

        Storage::disk('public')->makeDirectory('banners/demo');

        foreach ($banners as $bannerData) {
            $filename = 'banners/demo/banner-'.str($bannerData['title'])->slug().'.jpg';
            $this->generatePlaceholderImage(
                storage_path('app/public/'.$filename),
                1200,
                300,
                $bannerData['color']
            );

            Banner::query()->create([
                'cooperative_id' => $cooperative->id,
                'title' => $bannerData['title'],
                'image_path' => $filename,
                'link_url' => $bannerData['link_url'],
                'alt_text' => $bannerData['alt_text'],
                'status' => BannerStatus::Published->value,
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
        $light = imagecolorallocatealpha($image, 255, 255, 255, 40);

        imagefilledrectangle($image, 0, 0, $width, 4, $light);
        imagefilledrectangle($image, 0, $height - 4, $width, $height, $light);

        $fontSize = 5;
        $text = $width.'×'.$height;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $x = ($width - $textWidth) / 2;
        $y = ($height / 2) - 10;
        imagestring($image, $fontSize, (int) $x, (int) $y, $text, $white);

        $label = 'Banner Demo';
        $labelWidth = imagefontwidth($fontSize) * strlen($label);
        $lx = ($width - $labelWidth) / 2;
        $ly = ($height / 2) + 15;
        imagestring($image, $fontSize, (int) $lx, (int) $ly, $label, $white);

        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }
}
