<?php

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Storage;

class ManifestController extends Controller
{
    public function __invoke(SettingsService $settings)
    {
        $coop = $settings->activeCooperative();

        $name = $coop?->short_name ?? $coop?->name ?? config('app.name');
        $shortName = $coop?->short_name ?? 'Portal Ahli';
        $themeColor = $coop?->primary_color ?? '#0f766e';
        $bgColor = '#f8fafc';

        $icons = [];

        if ($coop?->logo_path) {
            $logoUrl = Storage::disk('public')->url($coop->logo_path);
            $icons = [
                ['src' => $logoUrl, 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $logoUrl, 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => $logoUrl, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ];
        }

        if (empty($icons)) {
            $icons = [
                ['src' => '/img/pwa/icon-192x192.svg', 'sizes' => '192x192', 'type' => 'image/svg+xml'],
                ['src' => '/img/pwa/icon-512x512.svg', 'sizes' => '512x512', 'type' => 'image/svg+xml'],
                ['src' => '/img/pwa/icon-512x512.svg', 'sizes' => '512x512', 'type' => 'image/svg+xml', 'purpose' => 'maskable'],
            ];
        }

        return response()->json([
            'name' => "{$name} - Portal Ahli",
            'short_name' => $shortName,
            'description' => 'Portal Ahli Koperasi',
            'start_url' => '/member/dashboard',
            'scope' => '/member/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => $themeColor,
            'background_color' => $bgColor,
            'icons' => $icons,
        ]);
    }
}
