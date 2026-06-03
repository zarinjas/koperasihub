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
        Storage::disk('public')->makeDirectory('financing/supporting-docs');
        Storage::disk('public')->makeDirectory('financing/document-templates');

        $this->generateRateImage('financing/rate-images/berpenjamin.jpg', [15, 118, 110], 'Berpenjamin');
        $this->generateRateImage('financing/rate-images/tanpa-penjamin.jpg', [29, 78, 216], 'Tanpa Penjamin');

        $berpenjamin = FinancingCategory::create([
            'cooperative_id' => $cooperative->id,
            'name' => 'Pembiayaan Berpenjamin',
            'slug' => 'pembiayaan-berpenjamin',
            'description' => 'Pembiayaan yang memerlukan penjamin daripada kalangan ahli koperasi.',
            'type' => FinancingCategoryType::Guaranteed,
            'icon' => 'HandCoins',
            'is_active' => true,
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
            'created_by' => $admin->id,
        ]);

        $productDefs = [
            // ── Produk Sedia Ada ──
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
                'rate_image_path' => 'financing/rate-images/berpenjamin.jpg',
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
                'category' => $tanpaPenjamin,
                'rate_image_path' => 'financing/rate-images/tanpa-penjamin.jpg',
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
                'rate_image_path' => 'financing/rate-images/berpenjamin.jpg',
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
                'rate_image_path' => 'financing/rate-images/tanpa-penjamin.jpg',
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
                'rate_image_path' => 'financing/rate-images/tanpa-penjamin.jpg',
            ],

            // ── Produk Baru ala UNIKEB ──
            [
                'name' => 'Pembiayaan Persekolahan',
                'slug' => 'pembiayaan-persekolahan',
                'description' => 'Pembiayaan khas untuk membantu ahli menampung yuran persekolahan anak-anak. Bayaran balik secara potongan gaji.',
                'min_amount' => 500,
                'max_amount' => 5000,
                'min_tenure_months' => 6,
                'max_tenure_months' => 36,
                'annual_rate_percent' => 3.50,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'requires_stamped_upload' => false,
                'category' => $tanpaPenjamin,
                'rate_image_path' => 'financing/rate-images/tanpa-penjamin.jpg',
                'sections' => [
                    [
                        'title' => 'Maklumat Pemohon',
                        'description' => 'Maklumat peribadi pemohon pembiayaan.',
                        'fields' => [
                            ['label' => 'Nama Penuh', 'type' => FinancingFieldType::MemberName, 'is_required' => true],
                            ['label' => 'No. Kad Pengenalan', 'type' => FinancingFieldType::MemberIdentityNo, 'is_required' => true],
                            ['label' => 'No. Telefon', 'type' => FinancingFieldType::MemberPhone, 'is_required' => true],
                            ['label' => 'Alamat', 'type' => FinancingFieldType::AddressMy, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Maklumat Pembiayaan',
                        'description' => 'Butiran pembiayaan persekolahan yang dipohon.',
                        'fields' => [
                            ['label' => 'Jumlah Pembiayaan', 'type' => FinancingFieldType::FinancingAmount, 'is_required' => true],
                            ['label' => 'Tempoh Pembiayaan', 'type' => FinancingFieldType::FinancingTenure, 'is_required' => true],
                            ['label' => 'Tujuan Pembiayaan', 'type' => FinancingFieldType::Select, 'is_required' => true, 'options_json' => ['Yuran Pendaftaran', 'Yuran Pengajian', 'Buku & Alat Tulis', 'Keperluan Asrama', 'Lain-lain']],
                            ['label' => 'Nama Institusi', 'type' => FinancingFieldType::ShortText, 'is_required' => true, 'help_text' => 'Nama sekolah / institusi pengajian'],
                            ['label' => 'Bilangan Anak Dalam Pendidikan', 'type' => FinancingFieldType::Number, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Dokumen Sokongan',
                        'description' => 'Sila muat naik dokumen berkaitan.',
                        'fields' => [
                            ['label' => 'Slip Gaji Terkini', 'type' => FinancingFieldType::File, 'is_required' => true, 'help_text' => 'Slip gaji 1 bulan terkini', 'validation_json' => ['max_size_kb' => 3072]],
                            ['label' => 'Penyata Yuran Sekolah', 'type' => FinancingFieldType::File, 'is_required' => false, 'help_text' => 'Penyata yuran dari institusi berkenaan', 'validation_json' => ['max_size_kb' => 3072]],
                        ],
                    ],
                    [
                        'title' => 'Terma & Syarat',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Nota Pembiayaan', 'type' => FinancingFieldType::Note, 'settings_json' => ['content' => 'Pembiayaan ini adalah untuk tujuan pendidikan sahaja. Pembayaran balik akan dibuat secara potongan gaji bulanan.']],
                            ['label' => 'Akuan Pemohon', 'type' => FinancingFieldType::DigitalSignature, 'is_required' => true],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pembiayaan Aidilfitri',
                'slug' => 'pembiayaan-aidilfitri',
                'description' => 'Pembiayaan khas sempena Hari Raya untuk membantu ahli menyambut Aidilfitri dengan lebih ceria. Bayaran balik sehingga 2 tahun.',
                'min_amount' => 500,
                'max_amount' => 5000,
                'min_tenure_months' => 3,
                'max_tenure_months' => 24,
                'annual_rate_percent' => 3.00,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'requires_stamped_upload' => false,
                'category' => $tanpaPenjamin,
                'rate_image_path' => 'financing/rate-images/tanpa-penjamin.jpg',
                'sections' => [
                    [
                        'title' => 'Maklumat Pemohon',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Nama Penuh', 'type' => FinancingFieldType::MemberName, 'is_required' => true],
                            ['label' => 'No. Kad Pengenalan', 'type' => FinancingFieldType::MemberIdentityNo, 'is_required' => true],
                            ['label' => 'No. Telefon', 'type' => FinancingFieldType::MemberPhone, 'is_required' => true],
                            ['label' => 'Jawatan', 'type' => FinancingFieldType::MemberPosition, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Butiran Pembiayaan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Jumlah Pembiayaan', 'type' => FinancingFieldType::FinancingAmount, 'is_required' => true],
                            ['label' => 'Tempoh Pembayaran', 'type' => FinancingFieldType::FinancingTenure, 'is_required' => true],
                            ['label' => 'Keperluan', 'type' => FinancingFieldType::Select, 'is_required' => true, 'options_json' => ['Pakaian Hari Raya', 'Persiapan Rumah', 'Keperluan Keluarga', 'Lain-lain']],
                            ['label' => 'Catatan', 'type' => FinancingFieldType::LongText, 'is_required' => false, 'help_text' => 'Sebarang maklumat tambahan (jika ada)'],
                        ],
                    ],
                    [
                        'title' => 'Dokumen',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Slip Gaji', 'type' => FinancingFieldType::File, 'is_required' => true, 'validation_json' => ['max_size_kb' => 3072]],
                        ],
                    ],
                    [
                        'title' => 'Pengesahan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Pengesahan', 'type' => FinancingFieldType::Note, 'settings_json' => ['content' => 'Saya mengaku bahawa pembiayaan ini akan digunakan untuk keperluan persiapan Hari Raya dan bersetuju dengan terma pembayaran balik.']],
                            ['label' => 'Tandatangan', 'type' => FinancingFieldType::DigitalSignature, 'is_required' => true],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pembiayaan Emas',
                'slug' => 'pembiayaan-emas',
                'description' => 'Pembiayaan untuk pembelian emas di kedai emas koperasi secara potongan gaji. Miliki emas tanpa perlu membayar tunai.',
                'min_amount' => 500,
                'max_amount' => 30000,
                'min_tenure_months' => 6,
                'max_tenure_months' => 60,
                'annual_rate_percent' => 3.50,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'requires_stamped_upload' => false,
                'category' => $tanpaPenjamin,
                'rate_image_path' => 'financing/rate-images/tanpa-penjamin.jpg',
                'sections' => [
                    [
                        'title' => 'Maklumat Ahli',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Nama Ahli', 'type' => FinancingFieldType::MemberName, 'is_required' => true],
                            ['label' => 'No. Ahli', 'type' => FinancingFieldType::MemberMemberNo, 'is_required' => true],
                            ['label' => 'No. Kad Pengenalan', 'type' => FinancingFieldType::MemberIdentityNo, 'is_required' => true],
                            ['label' => 'No. Telefon', 'type' => FinancingFieldType::MemberPhone, 'is_required' => true],
                            ['label' => 'Majikan', 'type' => FinancingFieldType::MemberEmployer, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Butiran Pembelian Emas',
                        'description' => 'Pembelian emas akan dibuat di Kedai Emas Koperasi.',
                        'fields' => [
                            ['label' => 'Jumlah Pembiayaan', 'type' => FinancingFieldType::FinancingAmount, 'is_required' => true],
                            ['label' => 'Tempoh (bulan)', 'type' => FinancingFieldType::FinancingTenure, 'is_required' => true],
                            ['label' => 'Jenis Emas', 'type' => FinancingFieldType::Select, 'is_required' => true, 'options_json' => ['Emas 916', 'Emas 999', 'Dinar Emas', 'Barang Kemas']],
                            ['label' => 'Anggaran Berat (gram)', 'type' => FinancingFieldType::Number, 'is_required' => false, 'help_text' => 'Anggaran berat emas yang ingin dibeli'],
                        ],
                    ],
                    [
                        'title' => 'Pengesahan & Terma',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Nota', 'type' => FinancingFieldType::Note, 'settings_json' => ['content' => 'Pembiayaan Emas adalah patuh syariah. Emas yang dibeli akan disimpan di kedai emas koperasi sehingga pembiayaan dilunaskan. Bayaran balik melalui potongan gaji bulanan.']],
                            ['label' => 'Akuan', 'type' => FinancingFieldType::DigitalSignature, 'is_required' => true],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pembiayaan Barangan & Perkhidmatan',
                'slug' => 'pembiayaan-barangan-perkhidmatan',
                'description' => 'Pembiayaan untuk pembelian barangan elektrik, perabot, dan perkhidmatan lain secara potongan gaji.',
                'min_amount' => 500,
                'max_amount' => 20000,
                'min_tenure_months' => 3,
                'max_tenure_months' => 60,
                'annual_rate_percent' => 4.00,
                'requires_guarantor' => false,
                'guarantor_count' => 0,
                'requires_stamped_upload' => false,
                'category' => $tanpaPenjamin,
                'rate_image_path' => 'financing/rate-images/tanpa-penjamin.jpg',
                'sections' => [
                    [
                        'title' => 'Maklumat Pemohon',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Nama', 'type' => FinancingFieldType::MemberName, 'is_required' => true],
                            ['label' => 'No. Kad Pengenalan', 'type' => FinancingFieldType::MemberIdentityNo, 'is_required' => true],
                            ['label' => 'No. Telefon', 'type' => FinancingFieldType::MemberPhone, 'is_required' => true],
                            ['label' => 'Majikan', 'type' => FinancingFieldType::MemberEmployer, 'is_required' => true],
                            ['label' => 'Pendapatan Bulanan', 'type' => FinancingFieldType::Currency, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Butiran Barangan / Perkhidmatan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Jenis Barangan', 'type' => FinancingFieldType::Select, 'is_required' => true, 'options_json' => ['Elektrik & Elektronik', 'Perabot', 'Komputer & IT', 'Telefon', 'Alatan Sukan', 'Perkhidmatan', 'Lain-lain']],
                            ['label' => 'Jumlah Pembiayaan', 'type' => FinancingFieldType::FinancingAmount, 'is_required' => true],
                            ['label' => 'Tempoh (bulan)', 'type' => FinancingFieldType::FinancingTenure, 'is_required' => true],
                            ['label' => 'Penerangan', 'type' => FinancingFieldType::LongText, 'is_required' => true, 'help_text' => 'Huraikan barangan atau perkhidmatan yang ingin dibiayai'],
                            ['label' => 'Nama Pembekal / Kedai', 'type' => FinancingFieldType::ShortText, 'is_required' => false],
                        ],
                    ],
                    [
                        'title' => 'Dokumen Sokongan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Slip Gaji', 'type' => FinancingFieldType::File, 'is_required' => true, 'validation_json' => ['max_size_kb' => 3072]],
                            ['label' => 'Sebutharga / Invois', 'type' => FinancingFieldType::File, 'is_required' => false, 'help_text' => 'Sebutharga dari pembekal (jika ada)', 'validation_json' => ['max_size_kb' => 5120]],
                        ],
                    ],
                    [
                        'title' => 'Pengesahan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Akuan', 'type' => FinancingFieldType::Note, 'settings_json' => ['content' => 'Saya mengesahkan bahawa pembiayaan ini akan digunakan untuk pembelian barangan / perkhidmatan seperti yang dinyatakan.']],
                            ['label' => 'Tandatangan', 'type' => FinancingFieldType::DigitalSignature, 'is_required' => true],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pembiayaan Ekspres Warga Emas',
                'slug' => 'pembiayaan-ekspres-warga-emas',
                'description' => 'Pembiayaan khas untuk ahli warga emas berumur 55 tahun ke atas dengan syarat mudah dan kadar keuntungan rendah.',
                'min_amount' => 500,
                'max_amount' => 10000,
                'min_tenure_months' => 6,
                'max_tenure_months' => 60,
                'annual_rate_percent' => 3.00,
                'requires_guarantor' => true,
                'guarantor_count' => 1,
                'requires_stamped_upload' => false,
                'category' => $berpenjamin,
                'rate_image_path' => 'financing/rate-images/berpenjamin.jpg',
                'sections' => [
                    [
                        'title' => 'Maklumat Pemohon',
                        'description' => 'Pemohon mestilah berumur 55 tahun ke atas.',
                        'fields' => [
                            ['label' => 'Nama', 'type' => FinancingFieldType::MemberName, 'is_required' => true],
                            ['label' => 'No. Kad Pengenalan', 'type' => FinancingFieldType::MemberIdentityNo, 'is_required' => true],
                            ['label' => 'Tarikh Lahir', 'type' => FinancingFieldType::MemberDob, 'is_required' => true],
                            ['label' => 'No. Telefon', 'type' => FinancingFieldType::MemberPhone, 'is_required' => true],
                            ['label' => 'Alamat', 'type' => FinancingFieldType::AddressMy, 'is_required' => true],
                            ['label' => 'Status Perkahwinan', 'type' => FinancingFieldType::MemberMaritalStatus, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Butiran Pembiayaan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Jumlah Dimohon', 'type' => FinancingFieldType::FinancingAmount, 'is_required' => true],
                            ['label' => 'Tempoh (bulan)', 'type' => FinancingFieldType::FinancingTenure, 'is_required' => true],
                            ['label' => 'Tujuan', 'type' => FinancingFieldType::Select, 'is_required' => true, 'options_json' => ['Keperluan Harian', 'Perubatan', 'Rumah', 'Cuti', 'Lain-lain']],
                            ['label' => 'Sumber Pendapatan', 'type' => FinancingFieldType::Select, 'is_required' => true, 'options_json' => ['Pencen', 'Hasil Sewaan', 'Bantuan Keluarga', 'Simpanan', 'Lain-lain']],
                        ],
                    ],
                    [
                        'title' => 'Maklumat Pasangan / Waris',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Nama Pasangan', 'type' => FinancingFieldType::MemberSpouseName, 'is_required' => false],
                            ['label' => 'No. Telefon Pasangan', 'type' => FinancingFieldType::MemberSpousePhone, 'is_required' => false],
                            ['label' => 'Nama Waris', 'type' => FinancingFieldType::ShortText, 'is_required' => true, 'help_text' => 'Nama waris terdekat yang boleh dihubungi'],
                            ['label' => 'No. Telefon Waris', 'type' => FinancingFieldType::Phone, 'is_required' => true],
                            ['label' => 'Alamat Waris', 'type' => FinancingFieldType::AddressBeneficiary, 'is_required' => false],
                        ],
                    ],
                    [
                        'title' => 'Dokumen & Pengesahan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Slip Pencen / Penyata Bank', 'type' => FinancingFieldType::File, 'is_required' => true, 'validation_json' => ['max_size_kb' => 3072]],
                            ['label' => 'Salinan Kad Pengenalan', 'type' => FinancingFieldType::File, 'is_required' => true, 'validation_json' => ['max_size_kb' => 2048]],
                            ['label' => 'Nota', 'type' => FinancingFieldType::Note, 'settings_json' => ['content' => 'Pembiayaan ini dikhususkan untuk ahli warga emas dengan kadar keuntungan rendah. Penjamin diperlukan daripada ahli koperasi yang masih bekerja.']],
                            ['label' => 'Tandatangan', 'type' => FinancingFieldType::DigitalSignature, 'is_required' => true],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Pembiayaan Hartanah',
                'slug' => 'pembiayaan-hartanah',
                'description' => 'Pembiayaan untuk pembelian rumah, pengubahsuaian, atau pelaburan hartanah. Tempoh bayaran balik sehingga 15 tahun.',
                'min_amount' => 10000,
                'max_amount' => 200000,
                'min_tenure_months' => 12,
                'max_tenure_months' => 180,
                'annual_rate_percent' => 5.00,
                'rate_tiers_json' => [
                    ['min_months' => 12, 'max_months' => 60, 'rate_percent' => 4.50],
                    ['min_months' => 61, 'max_months' => 120, 'rate_percent' => 5.00],
                    ['min_months' => 121, 'max_months' => 180, 'rate_percent' => 5.50],
                ],
                'requires_guarantor' => true,
                'guarantor_count' => 2,
                'requires_stamped_upload' => true,
                'stamped_upload_instructions' => 'Sila muat naik borang permohonan hartanah yang telah ditandatangani bersama penjamin dan dicop pengesahan.',
                'category' => $berpenjamin,
                'rate_image_path' => 'financing/rate-images/berpenjamin.jpg',
                'sections' => [
                    [
                        'title' => 'Maklumat Pemohon',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Nama Penuh', 'type' => FinancingFieldType::MemberName, 'is_required' => true],
                            ['label' => 'No. Kad Pengenalan', 'type' => FinancingFieldType::MemberIdentityNo, 'is_required' => true],
                            ['label' => 'Tarikh Lahir', 'type' => FinancingFieldType::MemberDob, 'is_required' => true],
                            ['label' => 'No. Telefon', 'type' => FinancingFieldType::MemberPhone, 'is_required' => true],
                            ['label' => 'E-mel', 'type' => FinancingFieldType::MemberEmail, 'is_required' => true],
                            ['label' => 'Status Perkahwinan', 'type' => FinancingFieldType::MemberMaritalStatus, 'is_required' => true],
                            ['label' => 'Nama Pasangan', 'type' => FinancingFieldType::MemberSpouseName, 'is_required' => false],
                            ['label' => 'Alamat Terkini', 'type' => FinancingFieldType::AddressMy, 'is_required' => true],
                            ['label' => 'Majikan', 'type' => FinancingFieldType::MemberEmployer, 'is_required' => true],
                            ['label' => 'Jabatan', 'type' => FinancingFieldType::MemberDepartment, 'is_required' => true],
                            ['label' => 'Pendapatan Bulanan', 'type' => FinancingFieldType::Currency, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Butiran Hartanah',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Tujuan', 'type' => FinancingFieldType::Select, 'is_required' => true, 'options_json' => ['Pembelian Rumah', 'Pengubahsuaian', 'Pembiayaan Semula', 'Pelaburan']],
                            ['label' => 'Jumlah Pembiayaan', 'type' => FinancingFieldType::FinancingAmount, 'is_required' => true],
                            ['label' => 'Tempoh (bulan)', 'type' => FinancingFieldType::FinancingTenure, 'is_required' => true],
                            ['label' => 'Alamat Hartanah', 'type' => FinancingFieldType::LongText, 'is_required' => true],
                            ['label' => 'Anggaran Nilai Hartanah', 'type' => FinancingFieldType::Currency, 'is_required' => true],
                        ],
                    ],
                    [
                        'title' => 'Dokumen Diperlukan',
                        'description' => 'Muat naik dokumen-dokumen berikut.',
                        'fields' => [
                            ['label' => 'Slip Gaji 3 Bulan Terkini', 'type' => FinancingFieldType::File, 'is_required' => true, 'validation_json' => ['max_size_kb' => 5120]],
                            ['label' => 'Penyata Bank 6 Bulan', 'type' => FinancingFieldType::File, 'is_required' => true, 'validation_json' => ['max_size_kb' => 5120]],
                            ['label' => 'Salinan Kad Pengenalan', 'type' => FinancingFieldType::File, 'is_required' => true, 'validation_json' => ['max_size_kb' => 3072]],
                            ['label' => 'Dokumen Hartanah', 'type' => FinancingFieldType::File, 'is_required' => false, 'help_text' => 'Geran / S&P / Dokumen berkaitan', 'validation_json' => ['max_size_kb' => 10240]],
                        ],
                    ],
                    [
                        'title' => 'Maklumat Penjamin',
                        'description' => 'Pembiayaan ini memerlukan 2 orang penjamin.',
                        'fields' => [
                            ['label' => 'Senarai Semak Penjamin', 'type' => FinancingFieldType::DocumentChecklist, 'settings_json' => ['items' => [['label' => 'Penjamin 1: Salinan Kad Pengenalan', 'required' => true], ['label' => 'Penjamin 1: Slip Gaji Terkini', 'required' => true], ['label' => 'Penjamin 2: Salinan Kad Pengenalan', 'required' => true], ['label' => 'Penjamin 2: Slip Gaji Terkini', 'required' => true]]]],
                        ],
                    ],
                    [
                        'title' => 'Terma & Pengesahan',
                        'description' => null,
                        'fields' => [
                            ['label' => 'Terma', 'type' => FinancingFieldType::RichText, 'settings_json' => ['content' => '<p><strong>Terma Pembiayaan Hartanah:</strong></p><ul><li>Kadar keuntungan adalah berdasarkan jumlah dan tempoh pembiayaan</li><li>Bayaran balik secara potongan gaji setiap bulan</li><li>Pembiayaan ini dilindungi oleh takaful</li><li>Penjamin perlu hadir ke pejabat koperasi untuk pengesahan dokumen</li></ul>']],
                            ['label' => 'Akuan & Tandatangan', 'type' => FinancingFieldType::SignatureBlock, 'is_required' => true, 'settings_json' => ['left_label' => 'Pemohon', 'right_label' => 'Pegawai Koperasi']],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($productDefs as $data) {
            $category = $data['category'];
            $sections = $data['sections'] ?? [];
            $rateImage = $data['rate_image_path'] ?? null;
            unset($data['category'], $data['sections'], $data['rate_image_path']);

            $product = FinancingProduct::create([
                ...$data,
                'cooperative_id' => $cooperative->id,
                'financing_category_id' => $category->id,
                'rate_image_path' => $rateImage,
                'is_active' => true,
                'created_by' => $admin->id,
            ]);

            if (! empty($sections)) {
                foreach ($sections as $order => $sectionData) {
                    $fields = $sectionData['fields'] ?? [];
                    unset($sectionData['fields']);

                    $section = FinancingProductSection::create([
                        ...$sectionData,
                        'financing_product_id' => $product->id,
                        'sort_order' => $order + 1,
                        'is_active' => true,
                    ]);

                    foreach ($fields as $fOrder => $field) {
                        $field['field_key'] = $this->fieldKey($field['label'], $field['type']);
                        FinancingProductField::create([
                            ...$field,
                            'financing_product_id' => $product->id,
                            'financing_product_section_id' => $section->id,
                            'sort_order' => $fOrder + 1,
                            'is_active' => true,
                            'validation_json' => $field['validation_json'] ?? null,
                            'options_json' => $field['options_json'] ?? null,
                            'settings_json' => $field['settings_json'] ?? null,
                            'help_text' => $field['help_text'] ?? null,
                        ]);
                    }
                }
            } else {
                $this->seedDefaultSectionsAndFields($product);
            }
        }

        // ── Sample Applications ──
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
                    'financing_product_id' => FinancingProduct::where('slug', 'pembiayaan-bai-al-inah')->first()?->id ?? $product1?->id,
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

            // Sample application for Pembiayaan Persekolahan (new product)
            $schoolProduct = FinancingProduct::where('slug', 'pembiayaan-persekolahan')->first();
            if ($schoolProduct && $members->count() >= 2) {
                $schoolApp = FinancingApplication::create([
                    'cooperative_id' => $cooperative->id,
                    'member_id' => $members[1]->id,
                    'financing_category_id' => $tanpaPenjamin->id,
                    'financing_product_id' => $schoolProduct->id,
                    'reference_no' => 'FIN-'.now()->format('Ymd').'-0004',
                    'amount_requested' => 3000,
                    'tenure_months' => 12,
                    'purpose' => 'Yuran persekolahan anak',
                    'monthly_income' => 3500,
                    'status' => FinancingApplicationStatus::Approved,
                    'submitted_at' => now()->subDays(5),
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now()->subDays(3),
                    'approved_by' => $admin->id,
                    'approved_at' => now()->subDays(1),
                ]);

                FinancingApplicationHistory::create([
                    'cooperative_id' => $cooperative->id,
                    'financing_application_id' => $schoolApp->id,
                    'actor_id' => $members[1]->user_id,
                    'action' => 'Hantar permohonan',
                    'to_status' => FinancingApplicationStatus::Submitted->value,
                    'created_at' => now()->subDays(5),
                ]);
                FinancingApplicationHistory::create([
                    'cooperative_id' => $cooperative->id,
                    'financing_application_id' => $schoolApp->id,
                    'actor_id' => $admin->id,
                    'action' => 'Permohonan diluluskan',
                    'from_status' => FinancingApplicationStatus::Submitted->value,
                    'to_status' => FinancingApplicationStatus::Approved->value,
                    'created_at' => now()->subDays(1),
                ]);
            }
        }
    }

    private function seedDefaultSectionsAndFields(FinancingProduct $product): void
    {
        $section = FinancingProductSection::create([
            'financing_product_id' => $product->id,
            'title' => 'Maklumat Permohonan',
            'description' => 'Sila lengkapkan maklumat berikut untuk permohonan pembiayaan.',
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
                'field_key' => $this->fieldKey($field['label'], $field['type']),
                'type' => $field['type'],
                'is_required' => $field['is_required'] ?? false,
                'help_text' => $field['help_text'] ?? null,
                'options_json' => $field['options_json'] ?? null,
                'validation_json' => $field['validation_json'] ?? null,
                'settings_json' => $field['settings_json'] ?? null,
                'is_active' => true,
            ]);
        }

        $infoSection = FinancingProductSection::create([
            'financing_product_id' => $product->id,
            'title' => 'Terma & Syarat',
            'description' => null,
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
            'is_active' => true,
        ]);
    }

    private function fieldKey(string $label, FinancingFieldType $type): string
    {
        $prefix = match ($type) {
            FinancingFieldType::MemberName => 'member_name',
            FinancingFieldType::MemberIdentityNo => 'member_identity_no',
            FinancingFieldType::MemberDob => 'member_dob',
            FinancingFieldType::MemberPhone => 'member_phone',
            FinancingFieldType::MemberEmail => 'member_email',
            FinancingFieldType::MemberPosition => 'member_position',
            FinancingFieldType::MemberEmployer => 'member_employer',
            FinancingFieldType::MemberMemberNo => 'member_member_no',
            FinancingFieldType::MemberEmploymentNo => 'member_employment_no',
            FinancingFieldType::MemberBank => 'member_bank',
            FinancingFieldType::MemberBankAccount => 'member_bank_account',
            FinancingFieldType::MemberMaritalStatus => 'member_marital_status',
            FinancingFieldType::AddressMy => 'address',
            FinancingFieldType::AddressSpouse => 'address_spouse',
            FinancingFieldType::AddressBeneficiary => 'address_beneficiary',
            FinancingFieldType::FinancingAmount => 'financing_amount',
            FinancingFieldType::FinancingTenure => 'financing_tenure',
            FinancingFieldType::MemberDepartment => 'member_department',
            FinancingFieldType::MemberSpouseName => 'member_spouse_name',
            FinancingFieldType::MemberSpousePhone => 'member_spouse_phone',
            default => null,
        };

        if ($prefix) {
            return $prefix;
        }

        // Provide stable slugs for common fields
        $slug = str($label)->slug('_');
        $slug->lower();

        return $slug->toString();
    }

    private function generateRateImage(string $path, array $rgb, string $label): void
    {
        $dir = dirname(storage_path('app/public/'.$path));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $image = imagecreatetruecolor(600, 300);

        $bg = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefill($image, 0, 0, $bg);

        $white = imagecolorallocate($image, 255, 255, 255);
        $light = imagecolorallocatealpha($image, 255, 255, 255, 40);

        imagefilledrectangle($image, 0, 0, 600, 6, $light);
        imagefilledrectangle($image, 0, 294, 600, 300, $light);

        $fontPath = null;
        foreach (['Helvetica.ttc' => '/System/Library/Fonts/Helvetica.ttc', 'DejaVuSans.ttf' => '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 'DejaVuSans.ttf' => '/usr/share/fonts/TTF/DejaVuSans.ttf'] as $file) {
            if (file_exists($file)) {
                $fontPath = $file;
                break;
            }
        }

        if ($fontPath) {
            $size = 36;
            $bbox = imagettfbbox($size, 0, $fontPath, $label);
            $textWidth = $bbox[2] - $bbox[0];
            $x = (600 - $textWidth) / 2;
            $y = 150 + ($size / 3);
            imagettftext($image, $size, 0, (int) $x, (int) $y, $white, $fontPath, $label);
        } else {
            $fontSize = 5;
            $textWidth = imagefontwidth($fontSize) * strlen($label);
            $x = (600 - $textWidth) / 2;
            $y = 145;
            imagestring($image, $fontSize, (int) $x, (int) $y, $label, $white);
        }

        imagejpeg($image, storage_path('app/public/'.$path), 85);
        imagedestroy($image);
    }
}
