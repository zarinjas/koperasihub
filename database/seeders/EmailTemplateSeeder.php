<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('status', 'active')->orderBy('id')->first();

        if (! $cooperative) {
            return;
        }

        $templates = [
            [
                'type' => 'membership_application_submitted_admin',
                'subject' => 'Permohonan Keahlian Baru: {{application_no}}',
                'body' => "Permohonan keahlian baharu telah diterima.\n\nNo Permohonan: {{application_no}}\nNama: {{full_name}}\nNo. KP: {{identity_no}}\nEmel: {{email}}\nTelefon: {{phone}}",
                'variables' => ['application_no', 'full_name', 'identity_no', 'email', 'phone', 'cooperative_name'],
            ],
            [
                'type' => 'membership_application_submitted_applicant',
                'subject' => 'Permohonan Keahlian Diterima: {{application_no}}',
                'body' => "Terima kasih kerana menghantar permohonan keahlian.\n\nNo Permohonan: {{application_no}}\n\nPermohonan anda akan diproses dalam tempoh 3 hari bekerja. Anda akan dimaklumkan melalui e-mel setelah permohonan diluluskan atau jika terdapat sebarang maklumat tambahan diperlukan.",
                'variables' => ['application_no', 'full_name', 'cooperative_name'],
            ],
            [
                'type' => 'membership_application_approved',
                'subject' => 'Permohonan Keahlian Diluluskan: {{application_no}}',
                'body' => "Tahniah! Permohonan keahlian anda telah diluluskan.\n\nNo Permohonan: {{application_no}}\nNo Ahli: {{member_no}}\n\nSila log masuk ke portal ahli untuk maklumat lanjut.",
                'variables' => ['application_no', 'full_name', 'member_no', 'cooperative_name'],
            ],
            [
                'type' => 'membership_application_rejected',
                'subject' => 'Permohonan Keahlian Ditolak: {{application_no}}',
                'body' => "Permohonan keahlian anda telah ditolak.\n\nNo Permohonan: {{application_no}}\nSebab: {{rejection_reason}}\n\nSila hubungi pihak koperasi untuk maklumat lanjut.",
                'variables' => ['application_no', 'full_name', 'rejection_reason', 'cooperative_name'],
            ],
            [
                'type' => 'financing_application_submitted',
                'subject' => 'Permohonan Pembiayaan Baru: {{reference_no}}',
                'body' => "Permohonan pembiayaan baharu telah diterima.\n\nNo Rujukan: {{reference_no}}\nAhli: {{member_name}}\nProduk: {{product_name}}\nJumlah: RM {{amount}}",
                'variables' => ['reference_no', 'member_name', 'product_name', 'amount', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_application_submitted',
                'subject' => 'Permohonan Ansuran Mudah Baru: {{application_no}}',
                'body' => "Permohonan Ansuran Mudah baharu telah diterima.\n\nNo Permohonan: {{application_no}}\nAhli: {{member_name}}\nProduk: {{product_name}}\nJumlah: RM {{amount}}",
                'variables' => ['application_no', 'member_name', 'product_name', 'amount', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_application_approved',
                'subject' => 'Permohonan Ansuran Mudah Diluluskan',
                'body' => "Permohonan Ansuran Mudah anda telah diluluskan.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}} - {{variant_name}}\nBayaran Bulanan: RM {{monthly_amount}}\nTempoh: {{tenure_months}} Bulan",
                'variables' => ['application_no', 'product_name', 'variant_name', 'monthly_amount', 'tenure_months', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_application_rejected',
                'subject' => 'Permohonan Ansuran Mudah Ditolak',
                'body' => "Permohonan Ansuran Mudah anda telah ditolak.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\nSebab: {{rejection_reason}}",
                'variables' => ['application_no', 'product_name', 'rejection_reason', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_guarantor_request',
                'subject' => 'Permintaan Menjadi Penjamin Ansuran Mudah',
                'body' => "Anda telah dipilih sebagai penjamin untuk permohonan Ansuran Mudah.\n\nAhli: {{member_name}}\nProduk: {{product_name}} - {{variant_name}}\nJumlah: RM {{amount}}",
                'variables' => ['member_name', 'product_name', 'variant_name', 'amount', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_guarantors_approved',
                'subject' => 'Semua Penjamin Telah Meluluskan Permohonan Anda',
                'body' => "Semua penjamin telah meluluskan permohonan Ansuran Mudah anda.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\n\nPermohonan anda kini dalam proses semakan pihak Koperasi.",
                'variables' => ['application_no', 'product_name', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_agreement_ready',
                'subject' => 'Perjanjian Ansuran Mudah Sedia Ditandatangani',
                'body' => "Perjanjian Ansuran Mudah anda telah sedia untuk ditandatangani.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\n\nSila log masuk ke portal ahli untuk menandatangani perjanjian secara digital.",
                'variables' => ['application_no', 'product_name', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_agreement_signed',
                'subject' => 'Perjanjian Ansuran Mudah Telah Ditandatangani',
                'body' => "Ahli telah menandatangani perjanjian Ansuran Mudah.\n\nNo Permohonan: {{application_no}}\nAhli: {{member_name}}\nProduk: {{product_name}}",
                'variables' => ['application_no', 'member_name', 'product_name', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_delivery_updated',
                'subject' => 'Status Penghantaran Ansuran Mudah Dikemaskini',
                'body' => "Status penghantaran pesanan anda telah dikemaskini.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\nStatus: {{delivery_status}}\nNo Tracking: {{tracking_no}}",
                'variables' => ['application_no', 'product_name', 'delivery_status', 'tracking_no', 'cooperative_name'],
            ],
            [
                'type' => 'ansuran_application_completed',
                'subject' => 'Pesanan Ansuran Mudah Selesai',
                'body' => "Pesanan Ansuran Mudah anda telah selesai.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}} - {{variant_name}}\n\nTerima kasih kerana menggunakan perkhidmatan Ansuran Mudah.",
                'variables' => ['application_no', 'product_name', 'variant_name', 'cooperative_name'],
            ],
            [
                'type' => 'referral_commission_earned',
                'subject' => 'Komisyen Rujukan Diterima',
                'body' => "Tahniah!\n\nAnda telah menerima komisyen rujukan sebanyak RM{{amount}} kerana memperkenalkan {{referred_name}}.\n\nPihak admin akan memproses pembayaran ke akaun bank anda dalam masa terdekat.",
                'variables' => ['amount', 'referred_name', 'cooperative_name'],
            ],
            [
                'type' => 'referral_commission_paid',
                'subject' => 'Komisyen Rujukan Telah Dibayar',
                'body' => "Komisyen rujukan sebanyak RM{{amount}} kerana memperkenalkan {{referred_name}} telah dibayar ke akaun bank anda.",
                'variables' => ['amount', 'referred_name', 'cooperative_name'],
            ],
            [
                'type' => 'member_password_reset',
                'subject' => 'Tetapan Semula Kata Laluan Portal Ahli',
                'body' => "Anda menerima e-mel ini kerana kami menerima permintaan tetapan semula kata laluan untuk akaun portal ahli anda.\n\nSila klik pautan di bawah untuk menetapkan semula kata laluan anda:\n\n{{reset_url}}\n\nPautan ini akan tamat tempoh dalam masa 60 minit.\n\nJika anda tidak membuat permintaan ini, sila abaikan e-mel ini.",
                'variables' => ['reset_url', 'cooperative_name'],
            ],
            [
                'type' => 'announcement',
                'subject' => '{{title}}',
                'body' => "{{summary}}",
                'variables' => ['title', 'summary', 'content', 'action_url', 'cooperative_name'],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->updateOrCreate(
                [
                    'cooperative_id' => $cooperative->id,
                    'type' => $template['type'],
                ],
                [
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'variables' => $template['variables'],
                    'is_active' => true,
                ],
            );
        }
    }
}
