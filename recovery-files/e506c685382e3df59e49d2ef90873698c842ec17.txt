<?php

namespace Database\Seeders;

use App\Models\AnsuranAgreementTemplate;
use App\Models\AnsuranCategory;
use App\Models\AnsuranProduct;
use App\Models\AnsuranProductImage;
use App\Models\AnsuranProductVariant;
use App\Models\AnsuranTenureOption;
use App\Models\Cooperative;
use Illuminate\Database\Seeder;

class AnsuranMudahDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::first();
        if (! $cooperative) {
            $cooperative = Cooperative::create([
                'name' => 'Koperasi Demo Berhad',
                'short_name' => 'KDemo',
                'registration_no' => 'KO-2025-0001',
                'slug' => 'koperasi-demo',
                'status' => 'active',
            ]);
        }

        $electronics = AnsuranCategory::create([
            'cooperative_id' => $cooperative->id,
            'name' => 'Elektronik',
            'slug' => 'elektronik',
            'description' => 'Produk elektronik seperti TV, peti sejuk, mesin basuh dan lain-lain.',
            'sort_order' => 1,
        ]);

        $furniture = AnsuranCategory::create([
            'cooperative_id' => $cooperative->id,
            'name' => 'Perabot',
            'slug' => 'perabot',
            'description' => 'Perabot rumah seperti sofa, katil, almari dan lain-lain.',
            'sort_order' => 2,
        ]);

        $appliances = AnsuranCategory::create([
            'cooperative_id' => $cooperative->id,
            'name' => 'Perkakas Rumah',
            'slug' => 'perkakas-rumah',
            'description' => 'Perkakas rumah seperti periuk, blender, vacuum dan lain-lain.',
            'sort_order' => 3,
        ]);

        $tv = AnsuranProduct::create([
            'cooperative_id' => $cooperative->id,
            'ansuran_category_id' => $electronics->id,
            'name' => 'TV Samsung U80000',
            'slug' => 'tv-samsung-u80000',
            'description' => '<p>TV Samsung U80000 dengan teknologi Crystal UHD 4K yang memberikan kualiti gambar yang luar biasa.</p><ul><li>Crystal Processor 4K</li><li>HDR10+</li><li>Smart TV dengan Tizen OS</li><li>Dolby Digital Plus</li></ul>',
            'min_down_payment_percent' => 20,
            'guarantor_count' => 1,
            'status' => 'aktif',
            'sort_order' => 1,
        ]);

        AnsuranProductVariant::insert([
            [
                'ansuran_product_id' => $tv->id,
                'name' => '48 inci',
                'sku' => 'TV-SAM-U80000-48',
                'price' => 1200.00,
                'stock' => 10,
                'attributes' => json_encode(['Saiz Skrin' => '48"', 'Resolusi' => '4K UHD']),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ansuran_product_id' => $tv->id,
                'name' => '55 inci',
                'sku' => 'TV-SAM-U80000-55',
                'price' => 1600.00,
                'stock' => 5,
                'attributes' => json_encode(['Saiz Skrin' => '55"', 'Resolusi' => '4K UHD']),
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ansuran_product_id' => $tv->id,
                'name' => '65 inci',
                'sku' => 'TV-SAM-U80000-65',
                'price' => 2000.00,
                'stock' => 3,
                'attributes' => json_encode(['Saiz Skrin' => '65"', 'Resolusi' => '4K UHD']),
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $sofaset = AnsuranProduct::create([
            'cooperative_id' => $cooperative->id,
            'ansuran_category_id' => $furniture->id,
            'name' => 'Set Sofa Moden',
            'slug' => 'set-sofa-moden',
            'description' => '<p>Set sofa moden selesa untuk ruang tamu anda. Diperbuat daripada fabrik berkualiti tinggi.</p>',
            'min_down_payment_percent' => 10,
            'guarantor_count' => 0,
            'status' => 'aktif',
            'sort_order' => 2,
        ]);

        AnsuranProductVariant::create([
            'ansuran_product_id' => $sofaset->id,
            'name' => '3+1+1 (Kelabu)',
            'sku' => 'SOFA-3P1P-GRY',
            'price' => 1800.00,
            'stock' => 4,
            'attributes' => json_encode(['Warna' => 'Kelabu', 'Material' => 'Fabrik']),
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $fridge = AnsuranProduct::create([
            'cooperative_id' => $cooperative->id,
            'ansuran_category_id' => $electronics->id,
            'name' => 'Peti Sejuk Panasonic',
            'slug' => 'peti-sejuk-panasonic',
            'description' => '<p>Peti sejuk Panasonic dengan teknologi Inverter yang menjimatkan tenaga elektrik.</p>',
            'min_down_payment_percent' => 20,
            'guarantor_count' => 2,
            'status' => 'aktif',
            'sort_order' => 3,
        ]);

        AnsuranProductVariant::insert([
            [
                'ansuran_product_id' => $fridge->id,
                'name' => '450L',
                'sku' => 'FRIDGE-PAN-450',
                'price' => 1500.00,
                'stock' => 2,
                'attributes' => json_encode(['Kapasiti' => '450L']),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ansuran_product_id' => $fridge->id,
                'name' => '550L',
                'sku' => 'FRIDGE-PAN-550',
                'price' => 2200.00,
                'stock' => 1,
                'attributes' => json_encode(['Kapasiti' => '550L']),
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $tenureOptions = [
            ['months' => 3, 'interest_rate_percent' => 0, 'label' => '3 Bulan'],
            ['months' => 6, 'interest_rate_percent' => 0, 'label' => '6 Bulan'],
            ['months' => 9, 'interest_rate_percent' => 2.00, 'label' => '9 Bulan'],
            ['months' => 12, 'interest_rate_percent' => 2.00, 'label' => '12 Bulan'],
            ['months' => 18, 'interest_rate_percent' => 3.00, 'label' => '18 Bulan'],
            ['months' => 24, 'interest_rate_percent' => 4.00, 'label' => '24 Bulan'],
            ['months' => 36, 'interest_rate_percent' => 6.00, 'label' => '36 Bulan'],
        ];

        foreach ($tenureOptions as $i => $tenure) {
            AnsuranTenureOption::create([
                'cooperative_id' => $cooperative->id,
                'months' => $tenure['months'],
                'interest_rate_percent' => $tenure['interest_rate_percent'],
                'label' => $tenure['label'],
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }

        AnsuranAgreementTemplate::create([
            'cooperative_id' => $cooperative->id,
            'name' => 'Perjanjian Ansuran Mudah Standard',
            'description' => 'Template standard untuk semua permohonan ansuran mudah.',
            'content' => <<<'HTML'
<h2 style="text-align: center;">PERJANJIAN ANSURAN MUDAH</h2>

<p><strong>No Permohonan:</strong> {{no_ahli}}_ANSURAN</p>

<p>Perjanjian ini dibuat pada <strong>{{tarikh_kontrak}}</strong> antara:</p>

<p><strong>PIHAK PERTAMA:</strong> Koperasi Demo Berhad</p>
<p><strong>PIHAK KEDUA:</strong> {{nama_ahli}} (No KP: {{no_kad_pengenalan}})</p>

<hr>

<h3>BUTIRAN PRODUK</h3>
<table style="width: 100%; border-collapse: collapse;">
    <tr><td style="padding: 5px;"><strong>Produk:</strong></td><td style="padding: 5px;">{{nama_produk}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Varian:</strong></td><td style="padding: 5px;">{{varian}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Harga Penuh:</strong></td><td style="padding: 5px;">{{harga_penuh}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Bayaran Pendahuluan:</strong></td><td style="padding: 5px;">{{bayaran_pendahuluan}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Jumlah Pembiayaan:</strong></td><td style="padding: 5px;">{{jumlah_pembiayaan}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Kadar Keuntungan:</strong></td><td style="padding: 5px;">{{kadar_keuntungan}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Tempoh Ansuran:</strong></td><td style="padding: 5px;">{{tempoh_ansuran}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Bayaran Bulanan:</strong></td><td style="padding: 5px;">{{bayaran_bulanan}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Jumlah Perlu Dibayar:</strong></td><td style="padding: 5px;">{{jumlah_perlu_dibayar}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Kaedah Penerimaan:</strong></td><td style="padding: 5px;">{{kaedah_penghantaran}}</td></tr>
    <tr><td style="padding: 5px;"><strong>Alamat Penghantaran:</strong></td><td style="padding: 5px;">{{alamat_penghantaran}}</td></tr>
</table>

<hr>

<h3>SYARAT PERJANJIAN</h3>
<ol>
    <li>Pihak Kedua bersetuju membayar ansuran bulanan sebanyak {{bayaran_bulanan}} selama {{tempoh_ansuran}}.</li>
    <li>Bayaran hendaklah dibuat sebelum atau pada tarikh akhir setiap bulan.</li>
    <li>Kegagalan membayar ansuran selama 3 bulan berturut-turut akan menyebabkan tindakan undang-undang diambil.</li>
    <li>Produk akan menjadi hak milik Pihak Kedua selepas semua bayaran diselesaikan.</li>
</ol>
HTML,
            'is_active' => true,
        ]);
    }
}
