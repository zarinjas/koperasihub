<?php

namespace Database\Seeders;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FinancingCategoryType;
use App\Enums\FinancingGuarantorStatus;
use App\Enums\MemberStatus;
use App\Models\Cooperative;
use App\Models\FinancingApplication;
use App\Models\FinancingCategory;
use App\Models\FinancingDocument;
use App\Models\FinancingGuarantor;
use App\Models\FinancingProduct;
use App\Models\Member;
use App\Models\Unit;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class FinancingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->first();

        if (! $cooperative) {
            return;
        }

        $loanUnitId = Unit::query()
            ->where('cooperative_id', $cooperative->id)
            ->where('slug', 'unit-pinjaman')
            ->value('id');

        $adminId = User::query()->where('email', 'admin@koperasihub.test')->value('id');
        $memberUser = User::query()->where('email', 'member@koperasihub.test')->first();
        $member = Member::query()
            ->where('cooperative_id', $cooperative->id)
            ->where('user_id', $memberUser?->id)
            ->first();

        if (! $memberUser || ! $member) {
            return;
        }

        $guarantorOneUser = User::query()->updateOrCreate([
            'email' => 'penjamin1@koperasihub.test',
        ], [
            'name' => 'Penjamin Demo Satu',
            'cooperative_id' => $cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
            'status' => 'active',
            'staff_id' => 'STF101',
            'password' => Hash::make('password'),
        ]);
        $guarantorOneUser->syncRoles([AccessControl::ROLE_MEMBER]);

        $guarantorTwoUser = User::query()->updateOrCreate([
            'email' => 'penjamin2@koperasihub.test',
        ], [
            'name' => 'Penjamin Demo Dua',
            'cooperative_id' => $cooperative->id,
            'role' => AccessControl::ROLE_MEMBER,
            'user_type' => AccessControl::ROLE_MEMBER,
            'status' => 'active',
            'staff_id' => 'STF102',
            'password' => Hash::make('password'),
        ]);
        $guarantorTwoUser->syncRoles([AccessControl::ROLE_MEMBER]);

        $guarantorOne = Member::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'member_no' => 'MBR-'.now()->format('Ymd').'-0101',
        ], [
            'user_id' => $guarantorOneUser->id,
            'full_name' => 'Penjamin Demo Satu',
            'identity_no' => '850101105551',
            'email' => $guarantorOneUser->email,
            'phone' => '0131000001',
            'address_line_1' => 'No. 3, Jalan Aman, Kajang',
            'country' => 'Malaysia',
            'occupation' => 'Eksekutif',
            'employer_name' => 'KoperasiHub Demo',
            'membership_status' => MemberStatus::Active->value,
            'joined_at' => now()->subMonths(12),
            'approved_at' => now()->subMonths(12),
            'approved_by' => $adminId,
        ]);

        $guarantorTwo = Member::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'member_no' => 'MBR-'.now()->format('Ymd').'-0102',
        ], [
            'user_id' => $guarantorTwoUser->id,
            'full_name' => 'Penjamin Demo Dua',
            'identity_no' => '860202105552',
            'email' => $guarantorTwoUser->email,
            'phone' => '0131000002',
            'address_line_1' => 'No. 4, Jalan Damai, Kajang',
            'country' => 'Malaysia',
            'occupation' => 'Penyelia',
            'employer_name' => 'KoperasiHub Demo',
            'membership_status' => MemberStatus::Active->value,
            'joined_at' => now()->subMonths(10),
            'approved_at' => now()->subMonths(10),
            'approved_by' => $adminId,
        ]);

        $guaranteedCategory = FinancingCategory::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'slug' => 'pembiayaan-berpenjamin',
        ], [
            'name' => 'Pembiayaan Berpenjamin',
            'description' => 'Kategori pembiayaan yang memerlukan persetujuan penjamin aktif.',
            'type' => FinancingCategoryType::Guaranteed->value,
            'rate_image_path' => null,
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        $nonGuaranteedCategory = FinancingCategory::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'slug' => 'pembiayaan-tanpa-penjamin',
        ], [
            'name' => 'Pembiayaan Tanpa Penjamin',
            'description' => 'Kategori pembiayaan ringkas tanpa keperluan penjamin.',
            'type' => FinancingCategoryType::NonGuaranteed->value,
            'rate_image_path' => null,
            'is_active' => true,
            'sort_order' => 2,
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        $products = [
            [
                'category' => $guaranteedCategory,
                'slug' => 'pembiayaan-peribadi-berpenjamin',
                'name' => 'Pembiayaan Peribadi Berpenjamin',
                'requires_guarantor' => true,
                'guarantor_count' => 2,
                'min_amount' => 3000,
                'max_amount' => 30000,
                'required_documents_json' => ['Salinan kad pengenalan', 'Slip gaji terkini'],
                'sort_order' => 1,
            ],
            [
                'category' => $guaranteedCategory,
                'slug' => 'pembiayaan-pendidikan-berpenjamin',
                'name' => 'Pembiayaan Pendidikan Berpenjamin',
                'requires_guarantor' => true,
                'guarantor_count' => 2,
                'min_amount' => 5000,
                'max_amount' => 25000,
                'required_documents_json' => ['Salinan kad pengenalan', 'Surat tawaran pengajian'],
                'sort_order' => 2,
            ],
            [
                'category' => $guaranteedCategory,
                'slug' => 'pembiayaan-kenderaan-berpenjamin',
                'name' => 'Pembiayaan Kenderaan Berpenjamin',
                'requires_guarantor' => true,
                'guarantor_count' => 2,
                'min_amount' => 8000,
                'max_amount' => 50000,
                'required_documents_json' => ['Salinan kad pengenalan', 'Sebutharga kenderaan'],
                'sort_order' => 3,
            ],
            [
                'category' => $nonGuaranteedCategory,
                'slug' => 'pembiayaan-kecil-tanpa-penjamin',
                'name' => 'Pembiayaan Kecil Tanpa Penjamin',
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'min_amount' => 1000,
                'max_amount' => 5000,
                'required_documents_json' => ['Salinan kad pengenalan'],
                'sort_order' => 4,
            ],
            [
                'category' => $nonGuaranteedCategory,
                'slug' => 'pembiayaan-barangan-tanpa-penjamin',
                'name' => 'Pembiayaan Barangan Tanpa Penjamin',
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'min_amount' => 1500,
                'max_amount' => 8000,
                'required_documents_json' => ['Salinan kad pengenalan', 'Sebutharga barangan'],
                'sort_order' => 5,
            ],
        ];

        foreach ($products as $product) {
            FinancingProduct::query()->updateOrCreate([
                'cooperative_id' => $cooperative->id,
                'slug' => $product['slug'],
            ], [
                'financing_category_id' => $product['category']->id,
                'unit_id' => $loanUnitId,
                'name' => $product['name'],
                'description' => $product['name'].' untuk kegunaan demo.',
                'min_amount' => $product['min_amount'],
                'max_amount' => $product['max_amount'],
                'min_tenure_months' => 6,
                'max_tenure_months' => 60,
                'requires_guarantor' => $product['requires_guarantor'],
                'guarantor_count' => $product['guarantor_count'],
                'required_documents_json' => $product['required_documents_json'],
                'is_active' => true,
                'sort_order' => $product['sort_order'],
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        $nonGuaranteedProduct = FinancingProduct::query()
            ->where('cooperative_id', $cooperative->id)
            ->where('slug', 'pembiayaan-kecil-tanpa-penjamin')
            ->first();
        $guaranteedProduct = FinancingProduct::query()
            ->where('cooperative_id', $cooperative->id)
            ->where('slug', 'pembiayaan-peribadi-berpenjamin')
            ->first();

        if (! $nonGuaranteedProduct || ! $guaranteedProduct) {
            return;
        }

        $nonGuaranteedApplication = FinancingApplication::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'reference_no' => 'FIN-'.now()->format('Ymd').'-0001',
        ], [
            'unit_id' => $loanUnitId,
            'member_id' => $member->id,
            'financing_category_id' => $nonGuaranteedCategory->id,
            'financing_product_id' => $nonGuaranteedProduct->id,
            'amount_requested' => 3500,
            'tenure_months' => 12,
            'purpose' => 'Permohonan demo untuk keperluan peribadi.',
            'monthly_income' => 3200,
            'monthly_commitment' => 650,
            'employment_notes' => 'Bekerja sepenuh masa.',
            'status' => FinancingApplicationStatus::UnderReview->value,
            'submitted_at' => now()->subDays(4),
            'reviewed_by' => $adminId,
            'reviewed_at' => now()->subDays(3),
            'decision_notes' => 'Dokumen lengkap dan sedang menunggu keputusan.',
        ]);

        $guaranteedApplication = FinancingApplication::query()->updateOrCreate([
            'cooperative_id' => $cooperative->id,
            'reference_no' => 'FIN-'.now()->format('Ymd').'-0002',
        ], [
            'unit_id' => $loanUnitId,
            'member_id' => $member->id,
            'financing_category_id' => $guaranteedCategory->id,
            'financing_product_id' => $guaranteedProduct->id,
            'amount_requested' => 12000,
            'tenure_months' => 24,
            'purpose' => 'Permohonan demo berpenjamin untuk kecemasan keluarga.',
            'monthly_income' => 3200,
            'monthly_commitment' => 650,
            'employment_notes' => 'Bekerja sepenuh masa.',
            'status' => FinancingApplicationStatus::GuarantorPending->value,
            'submitted_at' => now()->subDays(2),
        ]);

        FinancingGuarantor::query()->updateOrCreate([
            'financing_application_id' => $guaranteedApplication->id,
            'guarantor_member_id' => $guarantorOne->id,
        ], [
            'cooperative_id' => $cooperative->id,
            'status' => FinancingGuarantorStatus::Accepted->value,
            'consent_text' => 'Saya bersetuju untuk menjadi penjamin.',
            'consented_at' => now()->subDay(),
            'responded_at' => now()->subDay(),
        ]);

        FinancingGuarantor::query()->updateOrCreate([
            'financing_application_id' => $guaranteedApplication->id,
            'guarantor_member_id' => $guarantorTwo->id,
        ], [
            'cooperative_id' => $cooperative->id,
            'status' => FinancingGuarantorStatus::Pending->value,
        ]);

        Storage::disk('local')->put('financing/documents/demo-slip-gaji.pdf', 'demo financing document');

        FinancingDocument::query()->updateOrCreate([
            'financing_application_id' => $nonGuaranteedApplication->id,
            'label' => 'Slip gaji terkini',
        ], [
            'cooperative_id' => $cooperative->id,
            'uploaded_by' => $memberUser->id,
            'document_key' => 'slip-gaji-terkini',
            'file_path' => 'financing/documents/demo-slip-gaji.pdf',
            'file_name' => 'slip-gaji-terkini.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10240,
        ]);
    }
}
