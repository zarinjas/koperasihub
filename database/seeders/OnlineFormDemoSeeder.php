<?php

namespace Database\Seeders;

use App\Enums\FormFieldType;
use App\Enums\FormStatus;
use App\Enums\FormSubmissionMethod;
use App\Enums\FormVisibility;
use App\Models\Cooperative;
use App\Models\FormCategory;
use App\Models\OnlineForm;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class OnlineFormDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperativeId = Cooperative::query()
            ->where('slug', 'koperasi-demo-berhad')
            ->value('id');

        $adminId = User::query()
            ->where('email', 'admin@koperasihub.test')
            ->value('id');

        $categories = collect([
            ['name' => 'Keanggotaan', 'description' => 'Borang berkaitan pendaftaran, kemaskini, dan urusan ahli.', 'icon' => 'Users'],
            ['name' => 'Pembiayaan', 'description' => 'Borang permohonan pembiayaan peribadi, kenderaan, dan perniagaan.', 'icon' => 'Wallet'],
            ['name' => 'Simpanan & Pelaburan', 'description' => 'Borang pengeluaran simpanan, pelaburan, dan modal syer.', 'icon' => 'PiggyBank'],
            ['name' => 'Perlindungan', 'description' => 'Borang takaful, perlindungan anggota, dan tuntutan.', 'icon' => 'ShieldCheck'],
            ['name' => 'Kebajikan & Bantuan', 'description' => 'Borang bantuan kebajikan, pendidikan, dan kecemasan.', 'icon' => 'HeartHandshake'],
            ['name' => 'Kemudahan & Tempahan', 'description' => 'Borang tempahan bilik seminar, dewan, dan kenderaan.', 'icon' => 'Building2'],
        ])->map(function (array $item, int $index) use ($cooperativeId) {
            return FormCategory::query()->updateOrCreate([
                'cooperative_id' => $cooperativeId,
                'slug' => str($item['name'])->slug()->value(),
            ], [
                'name' => $item['name'],
                'description' => $item['description'],
                'icon' => $item['icon'],
                'is_active' => true,
            ]);
        })->keyBy('name');

        $categoryUnitMap = [
            'Keanggotaan' => 'unit-keanggotaan',
            'Pembiayaan' => 'unit-pinjaman',
            'Simpanan & Pelaburan' => 'unit-kewangan',
            'Perlindungan' => 'unit-kewangan',
            'Kebajikan & Bantuan' => 'unit-keanggotaan',
            'Kemudahan & Tempahan' => 'unit-sumber-manusia',
        ];

        $units = Unit::query()
            ->where('cooperative_id', $cooperativeId)
            ->get()
            ->keyBy('slug');

        foreach ($categoryUnitMap as $categoryName => $unitSlug) {
            $category = $categories->get($categoryName);
            $unit = $units->get($unitSlug);
            if ($category && $unit) {
                $category->update(['unit_id' => $unit->id]);
            }
        }

        $forms = [
            [
                'title' => 'Borang Kemaskini Maklumat Anggota',
                'category' => 'Keanggotaan',
                'visibility' => FormVisibility::MembersOnly,
                'status' => FormStatus::Published,
                'submission_method' => FormSubmissionMethod::OnlineOnly,
                'document_code' => 'FRM/ANG/002',
                'sections' => [
                    ['title' => 'Maklumat Semasa', 'fields' => [
                        ['label' => 'No. anggota', 'key' => 'member_no', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Nama penuh', 'key' => 'full_name', 'type' => FormFieldType::ShortText, 'required' => true],
                    ]],
                    ['title' => 'Maklumat Baharu', 'fields' => [
                        ['label' => 'No. telefon baharu', 'key' => 'new_phone', 'type' => FormFieldType::Phone, 'required' => false],
                        ['label' => 'Emel baharu', 'key' => 'new_email', 'type' => FormFieldType::Email, 'required' => false],
                        ['label' => 'Alamat baharu', 'key' => 'new_address', 'type' => FormFieldType::LongText, 'required' => false],
                        ['label' => 'Nama pasangan baharu', 'key' => 'new_spouse_name', 'type' => FormFieldType::ShortText, 'required' => false],
                        ['label' => 'Nama waris baharu', 'key' => 'new_nominee_name', 'type' => FormFieldType::ShortText, 'required' => false],
                    ]],
                    ['title' => 'Muat Naik Dokumen', 'fields' => [
                        ['label' => 'Salinan kad pengenalan (depan & belakang)', 'key' => 'ic_copy', 'type' => FormFieldType::File, 'required' => true],
                    ]],
                    ['title' => 'Pengesahan', 'fields' => [
                        ['label' => 'Akuan', 'key' => 'declaration', 'type' => FormFieldType::AgreementCheckbox, 'required' => true, 'help_text' => 'Saya mengesahkan semua maklumat kemaskini adalah benar.'],
                        ['label' => 'Tandatangan', 'key' => 'signature', 'type' => FormFieldType::Signature, 'required' => true],
                    ]],
                ],
            ],
            [
                'title' => 'Borang Permohonan Pembiayaan Peribadi',
                'category' => 'Pembiayaan',
                'visibility' => FormVisibility::MembersOnly,
                'status' => FormStatus::Published,
                'submission_method' => FormSubmissionMethod::RequiresStampedUpload,
                'stamped_upload_instructions' => "Sila cetak, dapatkan pengesahan majikan dengan cop rasmi, dan muat naik borang bercop.",
                'document_code' => 'FRM/PMB/001',
                'sections' => [
                    ['title' => 'Maklumat Pemohon', 'fields' => [
                        ['label' => 'No. anggota', 'key' => 'member_no', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Nama penuh', 'key' => 'full_name', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'No. telefon', 'key' => 'phone', 'type' => FormFieldType::Phone, 'required' => true],
                    ]],
                    ['title' => 'Maklumat Pembiayaan', 'fields' => [
                        ['label' => 'Jenis pembiayaan', 'key' => 'financing_type', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Pembiayaan Peribadi', 'Pembiayaan Pendidikan', 'Pembiayaan Perniagaan', 'Pembiayaan Rumah']],
                        ['label' => 'Jumlah dipohon (RM)', 'key' => 'amount_requested', 'type' => FormFieldType::Currency, 'required' => true],
                        ['label' => 'Tempoh bayaran (bulan)', 'key' => 'tenure_months', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['12', '24', '36', '48', '60', '72', '84', '96', '120']],
                        ['label' => 'Tujuan pembiayaan', 'key' => 'purpose', 'type' => FormFieldType::LongText, 'required' => true],
                    ]],
                    ['title' => 'Dokumen Sokongan', 'fields' => [
                        ['label' => 'Penyata gaji 3 bulan terkini', 'key' => 'salary_slip', 'type' => FormFieldType::File, 'required' => true],
                        ['label' => 'Penyata bank 6 bulan terkini', 'key' => 'bank_statement', 'type' => FormFieldType::File, 'required' => false],
                    ]],
                ],
            ],
            [
                'title' => 'Borang Permohonan Pembiayaan Kenderaan',
                'category' => 'Pembiayaan',
                'visibility' => FormVisibility::MembersOnly,
                'status' => FormStatus::Published,
                'submission_method' => FormSubmissionMethod::RequiresStampedUpload,
                'stamped_upload_instructions' => "Sila cetak dan dapatkan pengesahan penjual kenderaan beserta cop syarikat.",
                'document_code' => 'FRM/PMB/002',
                'sections' => [
                    ['title' => 'Maklumat Pemohon', 'fields' => [
                        ['label' => 'No. anggota', 'key' => 'member_no', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Nama penuh', 'key' => 'full_name', 'type' => FormFieldType::ShortText, 'required' => true],
                    ]],
                    ['title' => 'Maklumat Kenderaan', 'fields' => [
                        ['label' => 'Jenis kenderaan', 'key' => 'vehicle_type', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Kereta Baharu', 'Kereta Terpakai', 'Motosikal', 'Van/Kenderaan Komersial']],
                        ['label' => 'Jenama & model', 'key' => 'vehicle_model', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Harga kenderaan (RM)', 'key' => 'vehicle_price', 'type' => FormFieldType::Currency, 'required' => true],
                        ['label' => 'Jumlah deposit (RM)', 'key' => 'deposit_amount', 'type' => FormFieldType::Currency, 'required' => true],
                        ['label' => 'Jumlah dipohon (RM)', 'key' => 'amount_requested', 'type' => FormFieldType::Currency, 'required' => true],
                    ]],
                    ['title' => 'Dokumen Sokongan', 'fields' => [
                        ['label' => 'Sebut harga pembekal', 'key' => 'quotation', 'type' => FormFieldType::File, 'required' => true],
                        ['label' => 'Salinan kad pengenalan', 'key' => 'ic_copy', 'type' => FormFieldType::File, 'required' => true],
                    ]],
                ],
            ],
            [
                'title' => 'Borang Pengeluaran Simpanan Anggota',
                'category' => 'Simpanan & Pelaburan',
                'visibility' => FormVisibility::MembersOnly,
                'status' => FormStatus::Published,
                'submission_method' => FormSubmissionMethod::OnlineOnly,
                'document_code' => 'FRM/SMP/001',
                'sections' => [
                    ['title' => 'Maklumat Anggota', 'fields' => [
                        ['label' => 'No. anggota', 'key' => 'member_no', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Nama penuh', 'key' => 'full_name', 'type' => FormFieldType::ShortText, 'required' => true],
                    ]],
                    ['title' => 'Maklumat Pengeluaran', 'fields' => [
                        ['label' => 'Jenis pengeluaran', 'key' => 'withdrawal_type', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Pengeluaran Simpanan', 'Pengeluaran Modal Syer', 'Pengeluaran Simpanan Khas', 'Tuntutan Penuh (Tamat Keahlian)']],
                        ['label' => 'Jumlah pengeluaran (RM)', 'key' => 'withdrawal_amount', 'type' => FormFieldType::Currency, 'required' => true],
                        ['label' => 'Sebab pengeluaran', 'key' => 'reason', 'type' => FormFieldType::LongText, 'required' => true],
                    ]],
                    ['title' => 'Maklumat Bank', 'fields' => [
                        ['label' => 'Nama bank', 'key' => 'bank_name', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'No. akaun', 'key' => 'bank_account', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Nama pemegang akaun', 'key' => 'account_holder', 'type' => FormFieldType::ShortText, 'required' => true],
                    ]],
                    ['title' => 'Pengesahan', 'fields' => [
                        ['label' => 'Akuan', 'key' => 'declaration', 'type' => FormFieldType::AgreementCheckbox, 'required' => true, 'help_text' => 'Saya memahami syarat pengeluaran dan mengesahkan maklumat adalah benar.'],
                    ]],
                ],
            ],
            [
                'title' => 'Borang Tuntutan Bantuan Kecemasan',
                'category' => 'Kebajikan & Bantuan',
                'visibility' => FormVisibility::MembersOnly,
                'status' => FormStatus::Published,
                'submission_method' => FormSubmissionMethod::OnlineOnly,
                'document_code' => 'FRM/KBJ/001',
                'sections' => [
                    ['title' => 'Maklumat Pemohon', 'fields' => [
                        ['label' => 'No. anggota', 'key' => 'member_no', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Nama penuh', 'key' => 'full_name', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'No. telefon', 'key' => 'phone', 'type' => FormFieldType::Phone, 'required' => true],
                    ]],
                    ['title' => 'Maklumat Bantuan', 'fields' => [
                        ['label' => 'Jenis bantuan', 'key' => 'assistance_type', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Bantuan Perubatan', 'Bantuan Bencana/Kecemasan', 'Bantuan Kematian', 'Bantuan Pendidikan Anak', 'Bantuan Ibadah']],
                        ['label' => 'Penerangan keperluan', 'key' => 'description', 'type' => FormFieldType::LongText, 'required' => true],
                        ['label' => 'Jumlah dipohon (jika ada)', 'key' => 'amount', 'type' => FormFieldType::Currency, 'required' => false],
                    ]],
                    ['title' => 'Dokumen Sokongan', 'fields' => [
                        ['label' => 'Dokumen sokongan (bil, laporan, dsb.)', 'key' => 'supporting_doc', 'type' => FormFieldType::File, 'required' => true],
                    ]],
                ],
            ],
            [
                'title' => 'Borang Tempahan Bilik Seminar',
                'category' => 'Kemudahan & Tempahan',
                'visibility' => FormVisibility::Public,
                'status' => FormStatus::Published,
                'submission_method' => FormSubmissionMethod::OnlineOnly,
                'document_code' => 'FRM/KMD/001',
                'sections' => [
                    ['title' => 'Maklumat Pemohon', 'fields' => [
                        ['label' => 'Nama penuh', 'key' => 'full_name', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'No. telefon', 'key' => 'phone', 'type' => FormFieldType::Phone, 'required' => true],
                        ['label' => 'Emel', 'key' => 'email', 'type' => FormFieldType::Email, 'required' => true],
                        ['label' => 'Organisasi', 'key' => 'organization', 'type' => FormFieldType::ShortText, 'required' => true],
                    ]],
                    ['title' => 'Maklumat Tempahan', 'fields' => [
                        ['label' => 'Jenis bilik/dewan', 'key' => 'room_type', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Bilik Mesyuarat (20 org)', 'Bilik Seminar (50 org)', 'Dewan Serbaguna (150 org)', 'Dewan Utama (300 org)']],
                        ['label' => 'Tarikh mula', 'key' => 'start_date', 'type' => FormFieldType::Date, 'required' => true],
                        ['label' => 'Tarikh tamat', 'key' => 'end_date', 'type' => FormFieldType::Date, 'required' => true],
                        ['label' => 'Anggaran peserta', 'key' => 'participants', 'type' => FormFieldType::Number, 'required' => true],
                        ['label' => 'Tujuan penggunaan', 'key' => 'purpose', 'type' => FormFieldType::LongText, 'required' => true],
                    ]],
                    ['title' => 'Keperluan Tambahan', 'fields' => [
                        ['label' => 'Peralatan diperlukan', 'key' => 'equipment', 'type' => FormFieldType::Checkbox, 'required' => false, 'options' => ['LCD Projektor', 'Sistem PA', 'Whiteboard', 'Katering', 'Meja Pameran', 'Kerusi Tambahan']],
                        ['label' => 'Catatan khas', 'key' => 'special_notes', 'type' => FormFieldType::LongText, 'required' => false],
                    ]],
                ],
            ],
            [
                'title' => 'Borang Maklum Balas & Cadangan',
                'category' => 'Kebajikan & Bantuan',
                'visibility' => FormVisibility::Public,
                'status' => FormStatus::Published,
                'submission_method' => FormSubmissionMethod::OnlineOnly,
                'document_code' => 'FRM/KBJ/002',
                'sections' => [
                    ['title' => 'Maklumat Penghantar', 'fields' => [
                        ['label' => 'Nama', 'key' => 'full_name', 'type' => FormFieldType::ShortText, 'required' => false],
                        ['label' => 'Emel', 'key' => 'email', 'type' => FormFieldType::Email, 'required' => false],
                        ['label' => 'Kategori penghantar', 'key' => 'sender_type', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Anggota', 'Bukan Anggota', 'Pihak Luar']],
                    ]],
                    ['title' => 'Maklum Balas', 'fields' => [
                        ['label' => 'Jenis maklum balas', 'key' => 'feedback_type', 'type' => FormFieldType::Select, 'required' => true, 'options' => ['Cadangan Penambahbaikan', 'Maklum Balas Perkhidmatan', 'Cadangan Aktiviti', 'Lain-lain']],
                        ['label' => 'Tajuk', 'key' => 'subject', 'type' => FormFieldType::ShortText, 'required' => true],
                        ['label' => 'Penerangan', 'key' => 'description', 'type' => FormFieldType::LongText, 'required' => true],
                    ]],
                ],
            ],
        ];

        foreach ($forms as $formIndex => $definition) {
            $slug = str($definition['title'])->slug()->value();

            $form = OnlineForm::query()->updateOrCreate([
                'cooperative_id' => $cooperativeId,
                'slug' => $slug,
            ], [
                'form_category_id' => $categories[$definition['category']]->id,
                'created_by' => $adminId,
                'title' => $definition['title'],
                'description' => $definition['description'] ?? 'Borang rasmi yang boleh dihantar terus secara online.',
                'visibility' => $definition['visibility']->value,
                'status' => $definition['status']->value,
                'submission_method' => ($definition['submission_method'] ?? FormSubmissionMethod::OnlineOnly)->value,
                'stamped_upload_instructions' => $definition['stamped_upload_instructions'] ?? null,
                'success_message' => $definition['success_message'] ?? 'Borang anda berjaya dihantar dan akan diproses dalam tempoh terdekat.',
                'document_code' => $definition['document_code'],
                'revision_no' => '01',
                'effective_date' => now()->toDateString(),
                'document_title' => $definition['title'],
                'show_document_header' => true,
            ]);

            if (! isset($definition['sections'])) {
                continue;
            }

            foreach ($definition['sections'] as $sectionIndex => $sectionData) {
                $section = $form->sections()->updateOrCreate([
                    'online_form_id' => $form->id,
                    'title' => $sectionData['title'],
                ], [
                    'description' => $sectionData['description'] ?? null,
                    'is_active' => true,
                ]);

                foreach ($sectionData['fields'] as $fieldIndex => $fieldData) {
                    $section->fields()->updateOrCreate([
                        'online_form_id' => $form->id,
                        'field_key' => $fieldData['key'],
                    ], [
                        'form_section_id' => $section->id,
                        'label' => $fieldData['label'],
                        'type' => $fieldData['type']->value,
                        'placeholder' => $fieldData['placeholder'] ?? null,
                        'help_text' => $fieldData['help_text'] ?? null,
                        'is_required' => $fieldData['required'],
                        'options_json' => $fieldData['options'] ?? [],
                        'validation_json' => $fieldData['validation'] ?? [],
                        'settings_json' => $fieldData['settings'] ?? [],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}