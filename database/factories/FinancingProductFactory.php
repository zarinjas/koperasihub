<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancingProductFactory extends Factory
{
    protected $model = FinancingProduct::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Pembiayaan Peribadi Berpenjamin',
            'Pembiayaan Pendidikan Berpenjamin',
            'Pembiayaan Kecil Tanpa Penjamin',
        ]);

        return [
            'cooperative_id' => Cooperative::factory(),
            'financing_category_id' => FinancingCategory::factory(),
            'unit_id' => Unit::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'description' => fake()->sentence(),
            'eligibility_terms' => "Ahli aktif sekurang-kurangnya 6 bulan.\nTidak mempunyai tunggakan serius.",
            'product_terms' => "Kadar keuntungan tertakluk kepada kelulusan.\nTempoh bayaran balik mengikut polisi koperasi.",
            'application_notes' => 'Permohonan tertakluk kepada semakan dokumen dan kelulusan jawatankuasa.',
            'application_instructions' => 'Lengkapkan borang dalam talian, cetak, dapatkan tandatangan dan cop, kemudian muat naik semula salinan PDF.',
            'required_documents_note' => 'Sediakan semua dokumen sokongan sebelum menghantar borang lengkap bercop.',
            'officer_contact_name' => 'Pegawai Pembiayaan Demo',
            'officer_contact_phone' => '03-1234 5678',
            'officer_contact_email' => 'pembiayaan@koperasihub.test',
            'min_amount' => 1000,
            'max_amount' => 15000,
            'min_tenure_months' => 6,
            'max_tenure_months' => 60,
            'rate_image_path' => null,
            'annual_rate_percent' => 5.25,
            'rate_note' => 'Kadar demo tertakluk kepada semakan koperasi.',
            'requires_guarantor' => true,
            'guarantor_count' => 2,
            'required_documents_json' => ['Salinan IC', 'Slip gaji terkini'],
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 30),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
