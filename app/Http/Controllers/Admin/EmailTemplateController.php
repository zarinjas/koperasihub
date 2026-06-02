<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cooperative;
use App\Models\EmailTemplate;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    private const TEMPLATE_CATEGORIES = [
        'Permohonan Keahlian' => [
            'membership_application_submitted_admin' => [
                'label' => 'Pemberitahuan Admin — Permohonan Baharu',
                'variables' => ['application_no', 'full_name', 'identity_no', 'email', 'phone', 'cooperative_name'],
            ],
            'membership_application_submitted_applicant' => [
                'label' => 'Pengakuan Pemohon — Permohonan Diterima',
                'variables' => ['application_no', 'full_name', 'cooperative_name'],
            ],
            'membership_application_approved' => [
                'label' => 'Permohonan Diluluskan',
                'variables' => ['application_no', 'full_name', 'member_no', 'cooperative_name'],
            ],
            'membership_application_rejected' => [
                'label' => 'Permohonan Ditolak',
                'variables' => ['application_no', 'full_name', 'rejection_reason', 'cooperative_name'],
            ],
        ],
        'Pembiayaan' => [
            'financing_application_submitted' => [
                'label' => 'Pemberitahuan Admin — Pembiayaan Baharu',
                'variables' => ['reference_no', 'member_name', 'product_name', 'amount', 'cooperative_name'],
            ],
        ],
        'Ansuran Mudah' => [
            'ansuran_application_submitted' => [
                'label' => 'Pemberitahuan Admin — Ansuran Baharu',
                'variables' => ['application_no', 'member_name', 'product_name', 'amount', 'cooperative_name'],
            ],
            'ansuran_application_approved' => [
                'label' => 'Ansuran Diluluskan',
                'variables' => ['application_no', 'product_name', 'variant_name', 'monthly_amount', 'tenure_months', 'cooperative_name'],
            ],
            'ansuran_application_rejected' => [
                'label' => 'Ansuran Ditolak',
                'variables' => ['application_no', 'product_name', 'rejection_reason', 'cooperative_name'],
            ],
            'ansuran_guarantor_request' => [
                'label' => 'Permintaan Penjamin',
                'variables' => ['member_name', 'product_name', 'variant_name', 'amount', 'cooperative_name'],
            ],
            'ansuran_guarantors_approved' => [
                'label' => 'Semua Penjamin Diluluskan',
                'variables' => ['application_no', 'product_name', 'cooperative_name'],
            ],
            'ansuran_agreement_ready' => [
                'label' => 'Perjanjian Sedia Ditandatangani',
                'variables' => ['application_no', 'product_name', 'cooperative_name'],
            ],
            'ansuran_agreement_signed' => [
                'label' => 'Pemberitahuan Admin — Perjanjian Ditandatangani',
                'variables' => ['application_no', 'member_name', 'product_name', 'cooperative_name'],
            ],
            'ansuran_delivery_updated' => [
                'label' => 'Status Penghantaran Dikemaskini',
                'variables' => ['application_no', 'product_name', 'delivery_status', 'tracking_no', 'cooperative_name'],
            ],
            'ansuran_application_completed' => [
                'label' => 'Pesanan Selesai',
                'variables' => ['application_no', 'product_name', 'variant_name', 'cooperative_name'],
            ],
        ],
        'Komisyen Rujukan' => [
            'referral_commission_earned' => [
                'label' => 'Komisyen Diterima',
                'variables' => ['amount', 'referred_name', 'cooperative_name'],
            ],
            'referral_commission_paid' => [
                'label' => 'Komisyen Dibayar',
                'variables' => ['amount', 'referred_name', 'cooperative_name'],
            ],
        ],
        'Kata Laluan' => [
            'member_password_reset' => [
                'label' => 'Tetapan Semula Kata Laluan Ahli',
                'variables' => ['reset_url', 'cooperative_name'],
            ],
        ],
        'Pengumuman' => [
            'announcement' => [
                'label' => 'Pemberitahuan Pengumuman',
                'variables' => ['title', 'summary', 'content', 'action_url', 'cooperative_name'],
            ],
        ],
    ];

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function index(): Response
    {
        $templates = EmailTemplate::query()
            ->forCooperative($this->activeCooperative()?->id)
            ->get()
            ->keyBy('type')
            ->toArray();

        $systemTypes = [];
        foreach (self::TEMPLATE_CATEGORIES as $category => $types) {
            foreach ($types as $type => $meta) {
                $systemTypes[$type] = [
                    'label' => $meta['label'],
                    'variables' => $meta['variables'],
                    'category' => $category,
                ];
            }
        }

        return Inertia::render('Admin/Pages/EmailTemplates/Index', [
            'templates' => $templates,
            'templateCategories' => self::TEMPLATE_CATEGORIES,
            'systemTypes' => $systemTypes,
            'canEdit' => request()->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS) ?? false,
        ]);
    }

    public function edit(string $type): Response
    {
        $template = EmailTemplate::query()
            ->forCooperative($this->activeCooperative()?->id)
            ->where('type', $type)
            ->first();

        $isSystem = $this->isSystemType($type);
        $meta = $this->getTypeMeta($type);

        $defaults = null;
        if (! $template && $isSystem) {
            $defaults = $this->getDefaultTemplate($type);
        }

        return Inertia::render('Admin/Pages/EmailTemplates/Edit', [
            'template' => $template?->toArray() ?? [
                'type' => $type,
                'subject' => $defaults['subject'] ?? '',
                'body' => $defaults['body'] ?? '',
                'variables' => $meta['variables'] ?? [],
                'is_active' => true,
            ],
            'variables' => $meta['variables'] ?? [],
            'isSystem' => $isSystem,
            'typeLabel' => $meta['label'] ?? $type,
            'canEdit' => request()->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS) ?? false,
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS), 403);

        $cooperative = $this->activeCooperative();
        abort_unless($cooperative, 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string'],
        ]);

        $meta = $this->getTypeMeta($type);

        EmailTemplate::query()->updateOrCreate(
            [
                'cooperative_id' => $cooperative->id,
                'type' => $type,
            ],
            [
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'variables' => $validated['variables'] ?? $meta['variables'] ?? [],
                'is_active' => $request->boolean('is_active', true),
            ],
        );

        return redirect()
            ->route('admin.email-templates.index')
            ->with('status', 'Templat e-mel berjaya dikemas kini.');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS), 403);

        $cooperative = $this->activeCooperative();
        abort_unless($cooperative, 404);

        $validated = $request->validate([
            'type' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('email_templates', 'type')->where('cooperative_id', $cooperative->id),
                function ($attribute, $value, $fail) {
                    if ($this->isSystemType($value)) {
                        $fail('Jenis templat ini telah wujud sebagai templat sistem. Gunakan halaman edit untuk mengemaskini.');
                    }
                },
            ],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        EmailTemplate::query()->create([
            'cooperative_id' => $cooperative->id,
            'type' => $validated['type'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'variables' => [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('admin.email-templates.index')
            ->with('status', 'Templat e-mel tersuai berjaya dicipta.');
    }

    public function destroy(Request $request, string $type): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS), 403);

        if ($this->isSystemType($type)) {
            abort(403, 'Templat sistem tidak boleh dipadam.');
        }

        $cooperative = $this->activeCooperative();
        abort_unless($cooperative, 404);

        EmailTemplate::query()
            ->forCooperative($cooperative->id)
            ->where('type', $type)
            ->delete();

        return redirect()
            ->route('admin.email-templates.index')
            ->with('status', 'Templat e-mel tersuai berjaya dipadam.');
    }

    private function isSystemType(string $type): bool
    {
        foreach (self::TEMPLATE_CATEGORIES as $types) {
            if (array_key_exists($type, $types)) {
                return true;
            }
        }

        return false;
    }

    private function getTypeMeta(string $type): ?array
    {
        foreach (self::TEMPLATE_CATEGORIES as $types) {
            if (isset($types[$type])) {
                return $types[$type];
            }
        }

        return null;
    }

    private function getDefaultTemplate(string $type): ?array
    {
        return match ($type) {
            'membership_application_submitted_admin' => [
                'subject' => 'Permohonan Keahlian Baru: {{application_no}}',
                'body' => "Permohonan keahlian baharu telah diterima.\n\nNo Permohonan: {{application_no}}\nNama: {{full_name}}\nNo. KP: {{identity_no}}\nEmel: {{email}}\nTelefon: {{phone}}",
            ],
            'membership_application_submitted_applicant' => [
                'subject' => 'Permohonan Keahlian Diterima: {{application_no}}',
                'body' => "Terima kasih kerana menghantar permohonan keahlian.\n\nNo Permohonan: {{application_no}}\n\nPermohonan anda akan diproses dalam tempoh 3 hari bekerja. Anda akan dimaklumkan melalui e-mel setelah permohonan diluluskan atau jika terdapat sebarang maklumat tambahan diperlukan.",
            ],
            'membership_application_approved' => [
                'subject' => 'Permohonan Keahlian Diluluskan: {{application_no}}',
                'body' => "Tahniah! Permohonan keahlian anda telah diluluskan.\n\nNo Permohonan: {{application_no}}\nNo Ahli: {{member_no}}\n\nSila log masuk ke portal ahli untuk maklumat lanjut.",
            ],
            'membership_application_rejected' => [
                'subject' => 'Permohonan Keahlian Ditolak: {{application_no}}',
                'body' => "Permohonan keahlian anda telah ditolak.\n\nNo Permohonan: {{application_no}}\nSebab: {{rejection_reason}}\n\nSila hubungi pihak koperasi untuk maklumat lanjut.",
            ],
            'financing_application_submitted' => [
                'subject' => 'Permohonan Pembiayaan Baru: {{reference_no}}',
                'body' => "Permohonan pembiayaan baharu telah diterima.\n\nNo Rujukan: {{reference_no}}\nAhli: {{member_name}}\nProduk: {{product_name}}\nJumlah: RM {{amount}}",
            ],
            'ansuran_application_submitted' => [
                'subject' => 'Permohonan Ansuran Mudah Baru: {{application_no}}',
                'body' => "Permohonan Ansuran Mudah baharu telah diterima.\n\nNo Permohonan: {{application_no}}\nAhli: {{member_name}}\nProduk: {{product_name}}\nJumlah: RM {{amount}}",
            ],
            'ansuran_application_approved' => [
                'subject' => 'Permohonan Ansuran Mudah Diluluskan',
                'body' => "Permohonan Ansuran Mudah anda telah diluluskan.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}} - {{variant_name}}\nBayaran Bulanan: RM {{monthly_amount}}\nTempoh: {{tenure_months}} Bulan",
            ],
            'ansuran_application_rejected' => [
                'subject' => 'Permohonan Ansuran Mudah Ditolak',
                'body' => "Permohonan Ansuran Mudah anda telah ditolak.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\nSebab: {{rejection_reason}}",
            ],
            'ansuran_guarantor_request' => [
                'subject' => 'Permintaan Menjadi Penjamin Ansuran Mudah',
                'body' => "Anda telah dipilih sebagai penjamin untuk permohonan Ansuran Mudah.\n\nAhli: {{member_name}}\nProduk: {{product_name}} - {{variant_name}}\nJumlah: RM {{amount}}",
            ],
            'ansuran_guarantors_approved' => [
                'subject' => 'Semua Penjamin Telah Meluluskan Permohonan Anda',
                'body' => "Semua penjamin telah meluluskan permohonan Ansuran Mudah anda.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\n\nPermohonan anda kini dalam proses semakan pihak Koperasi.",
            ],
            'ansuran_agreement_ready' => [
                'subject' => 'Perjanjian Ansuran Mudah Sedia Ditandatangani',
                'body' => "Perjanjian Ansuran Mudah anda telah sedia untuk ditandatangani.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\n\nSila log masuk ke portal ahli untuk menandatangani perjanjian secara digital.",
            ],
            'ansuran_agreement_signed' => [
                'subject' => 'Perjanjian Ansuran Mudah Telah Ditandatangani',
                'body' => "Ahli telah menandatangani perjanjian Ansuran Mudah.\n\nNo Permohonan: {{application_no}}\nAhli: {{member_name}}\nProduk: {{product_name}}",
            ],
            'ansuran_delivery_updated' => [
                'subject' => 'Status Penghantaran Ansuran Mudah Dikemaskini',
                'body' => "Status penghantaran pesanan anda telah dikemaskini.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}}\nStatus: {{delivery_status}}\nNo Tracking: {{tracking_no}}",
            ],
            'ansuran_application_completed' => [
                'subject' => 'Pesanan Ansuran Mudah Selesai',
                'body' => "Pesanan Ansuran Mudah anda telah selesai.\n\nNo Permohonan: {{application_no}}\nProduk: {{product_name}} - {{variant_name}}\n\nTerima kasih kerana menggunakan perkhidmatan Ansuran Mudah.",
            ],
            'referral_commission_earned' => [
                'subject' => 'Komisyen Rujukan Diterima',
                'body' => "Tahniah!\n\nAnda telah menerima komisyen rujukan sebanyak RM{{amount}} kerana memperkenalkan {{referred_name}}.\n\nPihak admin akan memproses pembayaran ke akaun bank anda dalam masa terdekat.",
            ],
            'referral_commission_paid' => [
                'subject' => 'Komisyen Rujukan Telah Dibayar',
                'body' => "Komisyen rujukan sebanyak RM{{amount}} kerana memperkenalkan {{referred_name}} telah dibayar ke akaun bank anda.",
            ],
            'member_password_reset' => [
                'subject' => 'Tetapan Semula Kata Laluan Portal Ahli',
                'body' => "Anda menerima e-mel ini kerana kami menerima permintaan tetapan semula kata laluan untuk akaun portal ahli anda.\n\nSila klik pautan di bawah untuk menetapkan semula kata laluan anda:\n\n{{reset_url}}\n\nPautan ini akan tamat tempoh dalam masa 60 minit.\n\nJika anda tidak membuat permintaan ini, sila abaikan e-mel ini.",
            ],
            'announcement' => [
                'subject' => '{{title}}',
                'body' => "{{summary}}\n\n{{content}}",
            ],
            default => null,
        };
    }

    private function activeCooperative(): ?Cooperative
    {
        return $this->settings->activeCooperative();
    }
}