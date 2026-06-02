<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Seeder;

class CooperativeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->updateOrCreate([
            'slug' => 'koperasi-demo-berhad',
        ], [
            'name' => 'Koperasi Demo Berhad',
            'short_name' => 'Koperasi Demo',
            'registration_no' => 'D-0-0000',
            'logo_path' => null,
            'primary_color' => '#0F766E',
            'secondary_color' => '#1D4ED8',
            'address_line_1' => 'Aras 1, Bangunan Demo',
            'address_line_2' => 'Jalan Contoh 1',
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan Kuala Lumpur',
            'postcode' => '50450',
            'country' => 'Malaysia',
            'phone' => '+603-1234 5678',
            'email' => 'hello@koperasidemo.test',
            'whatsapp' => '+6012-345 6789',
            'website_url' => 'https://koperasidemo.test',
            'facebook_url' => 'https://facebook.com/koperasidemo',
            'instagram_url' => 'https://instagram.com/koperasidemo',
            'linkedin_url' => 'https://linkedin.com/company/koperasidemo',
            'footer_text' => 'Platform demo untuk pengurusan koperasi.',
            'status' => 'active',
        ]);

        app(SettingsService::class)->update($cooperative, [
            'brand' => [
                'name' => 'Koperasi Demo Berhad',
                'short_name' => 'Koperasi Demo',
                'registration_no' => 'D-0-0000',
                'logo_path' => null,
                'primary_color' => '#0F766E',
                'secondary_color' => '#1D4ED8',
            ],
            'contact' => [
                'address_line_1' => 'Aras 1, Bangunan Demo',
                'address_line_2' => 'Jalan Contoh 1',
                'city' => 'Kuala Lumpur',
                'state' => 'Wilayah Persekutuan Kuala Lumpur',
                'postcode' => '50450',
                'country' => 'Malaysia',
                'phone' => '+603-1234 5678',
                'email' => 'hello@koperasidemo.test',
                'whatsapp' => '+6012-345 6789',
                'website_url' => 'https://koperasidemo.test',
            ],
            'social' => [
                'facebook_url' => 'https://facebook.com/koperasidemo',
                'instagram_url' => 'https://instagram.com/koperasidemo',
                'linkedin_url' => 'https://linkedin.com/company/koperasidemo',
            ],
            'seo' => [
                'meta_title' => 'Koperasi Demo Berhad',
                'meta_description' => 'Laman demo untuk platform pengurusan koperasi putih label.',
            ],
            'system' => [
                'timezone' => 'Asia/Kuala_Lumpur',
                'date_format' => 'd/m/Y',
            ],
            'referral' => [
                'commission_amount' => '20.00',
                'commission_enabled' => '1',
                'minimum_active_days' => '0',
            ],
        ]);
    }
}