<?php

namespace Database\Seeders;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FinancingCategoryType;
use App\Enums\FinancingFieldType;
use App\Enums\FinancingGuarantorStatus;
use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingApplicationHistory;
use App\Models\FinancingCategory;
use App\Models\FinancingGuarantor;
use App\Models\FinancingProduct;
use App\Models\FinancingProductField;
use App\Models\FinancingProductSection;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class FinancingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::first();
        if (! $cooperative) {
            return;
        }

        $admin = User::role('super_admin')->first()
            ?? User::role('admin')->first()
            ?? User::factory()->create(['role' => 'super_admin']);

        Storage::disk('public')->makeDirectory('financing/rate-images');
        Storage::disk('public')->makeDirectory('financing/product-fields');
        Storage::disk('public')->makeDirectory('financing/documents');
        Storage::disk('public')->makeDirectory('financing/stamped-forms');
        Storage::disk('public')->makeDirectory('financing/signatures');

        $berpenjamin = FinancingCategory::create([
            'cooperative_id' => $cooperative->id,
            'name' => 'Pembiayaan Berpenjamin',
            'slug' => 'pembiayaan-berpenjamin',
            'description' => 'Pembiayaan yang memerlukan penjamin daripada kalangan ahli koperasi.',
            'type' => FinancingCategoryType::Guaranteed,
            'icon' => 'HandCoins',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $admin->id,
        ]);

        $tanpaPenjamin = FinancingCategory::create([
            'cooperative_id' => $cooperative->id,
            'name' => 'Pembiayaan Tanpa Penjamin',
            'slug' => 'pembiayaan-tanpa-penjamin',
            'description' => 'Pembiayaan tanpa penjamin dengan syarat kelayakan tertentu.',
            'type' => FinancingCategoryType::NonGuaranteed,
            'icon' => 'HandCoins',
            'is_active' => true,
            'sort_order' => 2,
            'created_by' => $admin->id,
        ]);

        $products = [
            [
                'name' => 'Pembiayaan Bai\' Al-Inah',
                'slug' => 'pembiayaan-bai-al-inah',
                'description' => 'Pembiayaan berasaskan konsep Bai\' Al-Inah untuk keperluan peribadi ahli.',
                'min_amount' => 1000,
                'max_amount' => 100000,
                'min_tenure_months' => 12,
                'max_tenure_months' => 120,
                'annual_rate_percent' => 4.50,
                'requires_guarantor' => true,
                'guarantor_count' => 1,
                'requires_stamped_upload' => true,
                'stamped_upload_instructions' => 'Sila cetak borang yang telah diisi, dapatkan cop pengesahan ketua jabatan, dan muat naik semula.',
                'category' => $berpenjamin,
            ],
            [
                'name' => 'Pembiayaan Ekpress',
                'slug' => 'pembiayaan-ekpress',
                'description' => 'Pembiayaan segera untuk keperluan mendesak dengan kelulusan pantas.',
                'min_amount' => 500,
                'max_amount' => 5000,
                'min_tenure_months' => 3,
                'max_tenure_months' => 12,
                'annual_rate_percent' => 5.00,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'requires_stamped_upload' => false,
                'category' => $berpenjamin,
            ],
            [
                'name' => 'Pembiayaan Takaful Kenderaan',
                'slug' => 'pembiayaan-takaful-kenderaan',
                'description' => 'Pembiayaan untuk perlindungan takaful kenderaan ahli.',
                'min_amount' => 500,
                'max_amount' => 3000,
                'min_tenure_months' => 6,
                'max_tenure_months' => 12,
                'annual_rate_percent' => 3.00,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'requires_stamped_upload' => true,
                'stamped_upload_instructions' => 'Sila muat naik borang takaful yang telah lengkap diisi dan dicop.',
                'category' => $berpenjamin,
            ],
            [
                'name' => 'Pembiayaan Barangan Kemas',
                'slug' => 'pembiayaan-barangan-kemas',
                'description' => 'Pembiayaan untuk pembelian barangan kemas dengan kadar yang kompetitif.',
                'min_amount' => 1000,
                'max_amount' => 20000,
                'min_tenure_months' => 12,
                'max_tenure_months' => 60,
                'annual_rate_percent' => 4.00,
                'requires_guarantor' => true,
                'guarantor_count' => 1,
                'requires_stamped_upload' => false,
                'category' => $tanpaPenjamin,
            ],
            [
                'name' => 'Pembiayaan Peribadi',
                'slug' => 'pembiayaan-peribadi',
                'description' => 'Pembiayaan peribadi untuk pelbagai kegunaan tanpa memerlukan penjamin.',
                'min_amount' => 1000,
                'max_amount' => 30000,
                'min_tenure_months' => 12,
                'max_tenure_months' => 84,
                'annual_rate_percent' => 4.80,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'requires_stamped_upload' => true,
                'stamped_upload_instructions' => 'Sila muat naik borang permohonan yang telah dicop oleh ketua jabatan.',
                'category' => $tanpaPenjamin,
            ],
        ];

        foreach ($products as $i => $data) {
            $category = $data['category'];
            unset($data['category']);

            $product = FinancingProduct::create([
                ...$data,
                'cooperative_id' => $cooperative->id,
                'financing_category_id' => $category->id,
                'is_active' => true,
                'sort_order' => $i + 1,
                'created_by' => $admin->id,
            ]);

            $this->seedSectionsAndFields($product);
        }

        $members = Member::take(5)->get();
        if ($members->isNotEmpty()) {
            $product1 = FinancingProduct::where('slug', 'pembiayaan-bai-al-inah')->first();

            if ($product1) {
                $app = FinancingApplication::create([
                    'cooperative_id' => $cooperative->id,
                    'member_id' => $members[0]->id,
                    'financing_category_id' => $berpenjamin->id,
                    'financing_product_id' => $product1->id,
                    'reference_no' => 'FIN-'.now()->format('Ymd').'-0001',
                    'amount_requested' => 15000,
                    'tenure_months' => 60,
                    'purpose' => 'Pengubahsuaian rumah',
                    'monthly_income' => 5000,
                    'monthly_commitment' => 1200,
                    'status' => FinancingApplicationStatus::InReview,
                    'submitted_at' => now(),
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

                FinancingApplicationHistory::create([
                    'cooperative_id' => $cooperative->id,
                    'financing_application_id' => $app->id,
                    'actor_id' => $members[0]->user_id,
                    'action' => 'Hantar permohonan',
                    'to_status' => FinancingApplicationStatus::Submitted->value,
                    'created_at' => now(),
                ]);
                FinancingApplicationHistory::create([
                    'cooperative_id' => $cooperative->id,
                    'financing_application_id' => $app->id,
                    'actor_id' => $admin->id,
                    'action' => 'Semakan dimulakan',
                    'from_status' => FinancingApplicationStatus::Submitted->value,
                    'to_status' => FinancingApplicationStatus::InReview->value,
                    'created_at' => now()->addMinutes(5),
                ]);
            }

            $product2 = FinancingProduct::where('slug', 'pembiayaan-peribadi')->first();

            if ($product2 && $members->count() >= 2) {
                $app2 = FinancingApplication::create([
                    'cooperative_id' => $cooperative->id,
                    'member_id' => $members[1]->id,
                    'financing_category_id' => $tanpaPenjamin->id,
                    'financing_product_id' => $product2->id,
                    'reference_no' => 'FIN-'.now()->format('Ymd').'-0002',
                    'amount_requested' => 8000,
                    'tenure_months' => 36,
                    'purpose' => 'Pembelian peralatan',
                    'monthly_income' => 3500,
                    'status' => FinancingApplicationStatus::PendingUpload,
                    'submitted_at' => now()->subDay(),
                ]);

                FinancingApplicationHistory::create([
                    'cooperative_id' => $cooperative->id,
                    'financing_application_id' => $app2->id,
                    'actor_id' => $members[1]->user_id,
                    'action' => 'Hantar permohonan',
                    'to_status' => FinancingApplicationStatus::Submitted->value,
                    'created_at' => now()->subDay(),
                ]);
            }

            if ($members->count() >= 4) {
                $guarantorApp = FinancingApplication::create([
                    'cooperative_id' => $cooperative->id,
                    'member_id' => $members[2]->id,
                    'financing_category_id' => $berpenjamin->id,
                    'financing_product_id' => $product1->id,
                    'reference_no' => 'FIN-'.now()->format('Ymd').'-0003',
                    'amount_requested' => 20000,
                    'tenure_months' => 48,
                    'purpose' => 'Pendidikan anak',
                    'monthly_income' => 6000,
                    'status' => FinancingApplicationStatus::PendingGuarantor,
                    'submitted_at' => now()->subHours(2),
                ]);

                FinancingGuarantor::create([
                    'cooperative_id' => $cooperative->id,
                    'financing_application_id' => $guarantorApp->id,
                    'guarantor_member_id' => $members[3]->id,
                    'status' => FinancingGuarantorStatus::Pending,
                ]);

                FinancingApplicationHistory::create([
                    'cooperative_id' => $cooperative->id,
                    'financing_application_id' => $guarantorApp->id,
                    'actor_id' => $members[2]->user_id,
                    'action' => 'Hantar permohonan',
                    'to_status' => FinancingApplicationStatus::Submitted->value,
                    'created_at' => now()->subHours(2),
                ]);
            }
        }
    }

    private function seedSectionsAndFields(FinancingProduct $product): void
    {
        $section = FinancingProductSection::create([
            'financing_product_id' => $product->id,
            'title' => 'Maklumat Permohonan',
            'description' => 'Sila lengkapkan maklumat berikut untuk permohonan pembiayaan.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $fields = [
            [
                'label' => 'Nota Penting',
                'type' => FinancingFieldType::InstructionText,
                'settings_json' => ['content' => 'Sila pastikan semua maklumat yang diisi adalah tepat dan benar. Maklumat palsu boleh menyebabkan permohonan ditolak.'],
            ],
            [
                'label' => 'Nama Penuh',
                'type' => FinancingFieldType::ShortText,
                'is_required' => true,
                'help_text' => 'Nama seperti dalam kad pengenalan',
            ],
            [
                'label' => 'No. Kad Pengenalan',
                'type' => FinancingFieldType::IdentityNo,
                'is_required' => true,
            ],
            [
                'label' => 'Alamat Terkini',
                'type' => FinancingFieldType::LongText,
                'is_required' => true,
            ],
            [
                'label' => 'Nombor Telefon',
                'type' => FinancingFieldType::Phone,
                'is_required' => true,
            ],
            [
                'label' => 'Jawatan',
                'type' => FinancingFieldType::ShortText,
                'is_required' => true,
            ],
            [
                'label' => 'Jenis Pekerjaan',
                'type' => FinancingFieldType::Select,
                'is_required' => true,
                'options_json' => ['Tetap', 'Kontrak', 'Sementara'],
            ],
            [
                'label' => 'Muat Naik Slip Gaji',
                'type' => FinancingFieldType::File,
                'is_required' => true,
                'help_text' => 'Sila muat naik slip gaji 3 bulan terkini dalam format PDF atau JPEG',
                'validation_json' => ['max_size_kb' => 5120],
            ],
            [
                'label' => 'Muat Naik Salinan Kad Pengenalan',
                'type' => FinancingFieldType::File,
                'is_required' => true,
                'help_text' => 'Salinan kad pengenalan depan dan belakang',
                'validation_json' => ['max_size_kb' => 3072],
            ],
        ];

        if ($product->requires_stamped_upload) {
            $fields[] = [
                'label' => 'Borang Permohonan Rasmi',
                'type' => FinancingFieldType::PdfDocument,
                'help_text' => 'Muat turun borang ini, isi, dapatkan cop ketua jabatan, dan muat naik semula di halaman status permohonan.',
            ];
        }

        foreach ($fields as $i => $field) {
            FinancingProductField::create([
                'financing_product_id' => $product->id,
                'financing_product_section_id' => $section->id,
                'label' => $field['label'],
                'field_key' => str()->slug($field['label']),
                'type' => $field['type'],
                'is_required' => $field['is_required'] ?? false,
                'help_text' => $field['help_text'] ?? null,
                'options_json' => $field['options_json'] ?? null,
                'validation_json' => $field['validation_json'] ?? null,
                'settings_json' => $field['settings_json'] ?? null,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }

        $infoSection = FinancingProductSection::create([
            'financing_product_id' => $product->id,
            'title' => 'Terma & Syarat',
            'description' => null,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        FinancingProductField::create([
            'financing_product_id' => $product->id,
            'financing_product_section_id' => $infoSection->id,
            'label' => 'Terma Pembiayaan',
            'field_key' => 'terma_pembiayaan',
            'type' => FinancingFieldType::RichText,
            'settings_json' => [
                'content' => '<p>Dengan menghantar permohonan ini, saya mengesahkan bahawa:</p><ul><li>Semua maklumat yang diberikan adalah benar</li><li>Saya memahami terma dan syarat pembiayaan</li><li>Saya bersetuju dengan kadar keuntungan yang ditetapkan</li><li>Saya akan membuat bayaran balik mengikut jadual yang ditetapkan</li></ul>',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }
}