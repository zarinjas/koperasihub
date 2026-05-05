<?php

namespace App\Http\Controllers\Member;

use App\Enums\FinancingApplicationStatus;
use App\Http\Requests\Member\CancelFinancingApplicationRequest;
use App\Http\Requests\Member\StoreFinancingApplicationRequest;
use App\Http\Requests\Member\UploadCompletedFinancingFormRequest;
use App\Http\Requests\Member\UploadFinancingApplicationDocumentRequest;
use App\Models\FinancingApplication;
use App\Models\FinancingDocument;
use App\Models\FinancingGuarantor;
use App\Models\FinancingProduct;
use App\Models\FinancingProductField;
use App\Services\Files\FinancingFileService;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingApplicationController extends MemberPortalController
{
    public function __construct(
        private readonly FinancingService $financing,
        private readonly FinancingFileService $files,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $member = $this->currentMember($request);

        $applications = $member->financingApplications()
            ->with(['product', 'category'])
            ->latest('submitted_at')
            ->get()
            ->map(fn (FinancingApplication $application) => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'category_name' => $application->category?->name,
                'product_name' => $application->product?->name,
                'amount_requested' => 'RM '.number_format((float) $application->amount_requested, 2),
                'tenure_months' => $application->tenure_months,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'show_url' => route('member.financing.applications.show', $application),
            ])
            ->all();

        return Inertia::render('Member/Pages/Financing/Applications/Index', [
            'applications' => $applications,
        ]);
    }

    public function create(Request $request): Response
    {
        $member = $this->currentMember($request);
        $productId = $request->integer('product');
        $product = FinancingProduct::query()
            ->where('cooperative_id', $member->cooperative_id)
            ->where('is_active', true)
            ->with(['category', 'productFields' => fn ($q) => $q->active()])
            ->findOrFail($productId);

        return Inertia::render('Member/Pages/Financing/Applications/Create', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'requires_guarantor' => $product->requires_guarantor,
                'guarantor_count' => $product->guarantor_count,
                'required_documents' => $product->required_documents_json ?? [],
                'required_documents_note' => $product->required_documents_note,
                'eligibility_terms' => $product->eligibility_terms,
                'product_terms' => $product->product_terms,
                'application_notes' => $product->application_notes,
                'application_instructions' => $product->application_instructions,
                'officer_contact_name' => $product->officer_contact_name,
                'officer_contact_phone' => $product->officer_contact_phone,
                'officer_contact_email' => $product->officer_contact_email,
                'product_documents' => $this->serializeProductDocuments($product),
                'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
                'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
                'min_tenure_months' => $product->min_tenure_months,
                'max_tenure_months' => $product->max_tenure_months,
                'category_name' => $product->category?->name,
                'rate_image_url' => $product->rate_image_path ? Storage::disk('public')->url($product->rate_image_path) : null,
                'product_fields' => $product->productFields->map(fn (FinancingProductField $field) => [
                    'id' => $field->id,
                    'label' => $field->label,
                    'field_key' => $field->field_key,
                    'type' => $field->type,
                    'placeholder' => $field->placeholder,
                    'help_text' => $field->help_text,
                    'is_required' => $field->is_required,
                    'options_json' => $field->options_json ?? [],
                    'settings_json' => $field->settings_json ?? [],
                ])->all(),
            ],
            'member' => [
                'full_name' => $member->full_name,
                'member_no' => $member->member_no,
                'occupation' => $member->occupation,
                'employer_name' => $member->employer_name,
            ],
            'guarantorSearchUrl' => route('member.financing.guarantor-search'),
        ]);
    }

    public function store(StoreFinancingApplicationRequest $request): RedirectResponse
    {
        $application = $this->financing->submitApplication([
            ...$request->validated(),
            'documents' => $request->file('documents', []),
        ], $this->currentMember($request), $request->user());

        return redirect()
            ->route('member.financing.applications.show', $application)
            ->with('status', 'Permohonan pembiayaan berjaya dihantar.');
    }

    public function show(Request $request, FinancingApplication $application): Response
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);

        $application->load(['category', 'product', 'documents', 'guarantors.guarantorMember.user', 'histories.actor', 'canceller']);

        return Inertia::render('Member/Pages/Financing/Applications/Show', [
            'application' => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'category_name' => $application->category?->name,
                'product_name' => $application->product?->name,
                'amount_requested' => 'RM '.number_format((float) $application->amount_requested, 2),
                'tenure_months' => $application->tenure_months,
                'purpose' => $application->purpose,
                'monthly_income' => $application->monthly_income !== null ? 'RM '.number_format((float) $application->monthly_income, 2) : null,
                'monthly_commitment' => $application->monthly_commitment !== null ? 'RM '.number_format((float) $application->monthly_commitment, 2) : null,
                'employment_notes' => $application->employment_notes,
                'decision_notes' => $application->decision_notes,
                'rejection_reason' => $application->rejection_reason,
                'cancellation_reason' => $application->cancellation_reason,
                'cancelled_at' => $application->cancelled_at?->format('d/m/Y H:i'),
                'cancelled_by_name' => $application->canceller?->name,
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'print_url' => route('member.financing.applications.print', $application),
                'cancel_url' => route('member.financing.applications.cancel', $application),
                'can_cancel' => in_array($application->status, FinancingApplicationStatus::memberCancellable(), true),
                'approved_amount' => $application->approved_amount !== null ? 'RM '.number_format((float) $application->approved_amount, 2) : null,
                'approved_tenure_months' => $application->approved_tenure_months,
                'product_terms' => [
                    'eligibility_terms' => $application->product?->eligibility_terms,
                    'product_terms' => $application->product?->product_terms,
                    'application_notes' => $application->product?->application_notes,
                    'application_instructions' => $application->product?->application_instructions,
                    'required_documents_note' => $application->product?->required_documents_note,
                    'required_documents' => $application->product?->required_documents_json ?? [],
                ],
                'product_documents' => $application->product ? $this->serializeProductDocuments($application->product) : [],
                'completed_form' => [
                    'uploaded' => filled($application->completed_form_pdf_path),
                    'file_name' => $application->completed_form_original_name,
                    'uploaded_at' => $application->completed_form_uploaded_at?->format('d/m/Y H:i'),
                    'download_url' => $application->completed_form_pdf_path ? route('member.financing.applications.completed-form.download', $application) : null,
                    'upload_url' => route('member.financing.applications.completed-form.store', $application),
                ],
                'documents' => $application->documents->map(fn (FinancingDocument $document) => [
                    'id' => $document->id,
                    'label' => $document->label,
                    'file_name' => $document->file_name,
                    'uploaded_at' => $document->created_at?->format('d/m/Y H:i'),
                    'download_url' => route('member.financing.applications.documents.download', [$application, $document]),
                ])->all(),
                'guarantors' => $application->guarantors->map(fn ($guarantor) => [
                    'id' => $guarantor->id,
                    'name' => $guarantor->guarantorMember?->full_name,
                    'member_no' => $guarantor->guarantorMember?->member_no,
                    'status' => $guarantor->status->value,
                    'status_label' => $guarantor->status->label(),
                    'responded_at' => $guarantor->responded_at?->format('d/m/Y H:i'),
                    'rejection_reason' => $guarantor->rejection_reason,
                ])->all(),
                'histories' => $application->histories->map(fn ($historyItem) => [
                    'id' => $historyItem->id,
                    'action' => $historyItem->action,
                    'action_label' => $this->historyActionLabel($historyItem->action),
                    'actor_name' => $historyItem->actor?->name,
                    'notes' => $historyItem->notes,
                    'created_at' => $historyItem->created_at?->format('d/m/Y H:i'),
                ])->all(),
                'next_step' => $this->nextStep($application),
            ],
            'canUploadAdditionalDocuments' => $application->status === FinancingApplicationStatus::IncompleteDocuments,
            'canUploadCompletedForm' => $this->canUploadCompletedForm($application),
        ]);
    }

    public function print(Request $request, FinancingApplication $application): Response
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);

        $application->load(['member.user', 'category', 'product.unit', 'documents', 'guarantors.guarantorMember.user']);

        return Inertia::render('Member/Pages/Financing/Applications/Print', [
            'pack' => $this->serializePrintPack($application, route('member.financing.applications.show', $application)),
        ]);
    }

    public function uploadDocument(UploadFinancingApplicationDocumentRequest $request, FinancingApplication $application): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);

        $this->financing->uploadAdditionalDocument($application, $request->file('file'), $request->user(), $request->validated('label'));

        return back()->with('status', 'Dokumen tambahan berjaya dimuat naik.');
    }

    public function cancel(CancelFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);

        $this->financing->cancelByApplicant($application, $request->user(), $request->validated('cancellation_reason'));

        return back()->with('status', 'Permohonan pembiayaan anda telah dibatalkan.');
    }

    public function uploadCompletedForm(UploadCompletedFinancingFormRequest $request, FinancingApplication $application): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);

        $this->financing->uploadCompletedForm($application, $request->file('completed_form'), $request->user());

        return back()->with('status', 'Borang lengkap bercop berjaya dimuat naik.');
    }

    public function downloadDocument(Request $request, FinancingApplication $application, FinancingDocument $document): StreamedResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);
        abort_unless($document->financing_application_id === $application->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $this->files->downloadName($document));
    }

    public function downloadCompletedForm(Request $request, FinancingApplication $application): StreamedResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);
        abort_unless($application->completed_form_pdf_path && Storage::disk('local')->exists($application->completed_form_pdf_path), 404);

        return Storage::disk('local')->download(
            $application->completed_form_pdf_path,
            $application->completed_form_original_name ?: 'borang-lengkap-bercop.pdf'
        );
    }

    private function historyActionLabel(string $action): string
    {
        return match ($action) {
            'submitted' => 'Permohonan dihantar',
            'guarantor_request_created' => 'Permintaan penjamin dihantar',
            'guarantor_accepted' => 'Seorang penjamin bersetuju',
            'all_guarantors_accepted' => 'Semua penjamin bersetuju',
            'guarantor_rejected' => 'Seorang penjamin tidak bersetuju',
            'completed_form_uploaded' => 'Borang lengkap bercop dimuat naik',
            'under_review' => 'Permohonan dalam semakan',
            'incomplete_documents' => 'Dokumen tambahan diminta',
            'document_uploaded' => 'Dokumen tambahan dimuat naik',
            'approved' => 'Permohonan diluluskan',
            'rejected' => 'Permohonan ditolak',
            'cancelled' => 'Permohonan dibatalkan',
            'closed' => 'Permohonan ditutup',
            default => Str::of($action)->replace('_', ' ')->title()->toString(),
        };
    }

    private function serializeProductDocuments(FinancingProduct $product): array
    {
        return collect(FinancingProduct::PRODUCT_DOCUMENTS)
            ->map(function (array $definition, string $key) use ($product): ?array {
                $path = $product->{$definition['path']};

                if (! $path) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => $definition['label'],
                    'download_label' => $definition['download_label'],
                    'file_name' => $product->{$definition['name']} ?: basename($path),
                    'download_url' => route('member.financing.products.documents.download', [$product, $key]),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function canUploadCompletedForm(FinancingApplication $application): bool
    {
        if ($application->status === FinancingApplicationStatus::PendingCompletedForm) {
            return true;
        }

        return $application->status === FinancingApplicationStatus::Submitted && ! $application->reviewed_at
            || $application->status === FinancingApplicationStatus::IncompleteDocuments;
    }

    private function nextStep(FinancingApplication $application): array
    {
        if ($application->status === FinancingApplicationStatus::PendingCompletedForm) {
            return [
                'title' => 'Tindakan Seterusnya',
                'description' => 'Sila pratonton dan cetak borang permohonan yang telah dilengkapkan. Dapatkan tandatangan serta cop pengesahan yang diperlukan, kemudian muat naik semula borang lengkap dalam format PDF.',
            ];
        }

        if ($application->status === FinancingApplicationStatus::GuarantorPending) {
            return [
                'title' => 'Menunggu Maklum Balas Penjamin',
                'description' => 'Permohonan anda sedang menunggu semua penjamin memberikan persetujuan sebelum langkah seterusnya boleh diteruskan.',
            ];
        }

        if ($application->status === FinancingApplicationStatus::IncompleteDocuments) {
            return [
                'title' => 'Dokumen Tambahan Diperlukan',
                'description' => 'Sila semak catatan admin dan muat naik dokumen tambahan yang diminta secepat mungkin.',
            ];
        }

        if ($application->status === FinancingApplicationStatus::Cancelled) {
            return [
                'title' => 'Permohonan Dibatalkan',
                'description' => 'Permohonan ini telah dibatalkan dan tidak lagi menerima dokumen atau tindakan baharu.',
            ];
        }

        return [
            'title' => 'Status Permohonan',
            'description' => 'Semak status permohonan anda dari semasa ke semasa melalui halaman ini.',
        ];
    }

    private function serializePrintPack(FinancingApplication $application, string $backUrl): array
    {
        $appSettings = $this->settings->shared();
        $cooperative = $appSettings['cooperative'] ?? [];
        $contact = $appSettings['contact'] ?? [];

        return [
            'back_url' => $backUrl,
            'cooperative' => [
                'name' => $cooperative['name'] ?? config('app.name'),
                'registration_no' => $cooperative['registration_no'] ?? null,
                'logo_url' => $cooperative['logo_url'] ?? null,
                'phone' => $contact['phone'] ?? null,
                'email' => $contact['email'] ?? null,
                'address' => collect([
                    $contact['address_line_1'] ?? null,
                    $contact['address_line_2'] ?? null,
                    collect([$contact['postcode'] ?? null, $contact['city'] ?? null, $contact['state'] ?? null])->filter()->implode(' '),
                ])->filter()->implode(', '),
            ],
            'application' => [
                'reference_no' => $application->reference_no,
                'status_label' => $application->status->label(),
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'print_generated_at' => now()->format('d/m/Y H:i'),
                'product_name' => $application->product?->name,
                'category_name' => $application->category?->name,
                'unit_name' => $application->product?->unit?->name,
                'amount_requested' => 'RM '.number_format((float) $application->amount_requested, 2),
                'tenure_months' => $application->tenure_months,
                'purpose' => $application->purpose,
                'monthly_income' => $application->monthly_income !== null ? 'RM '.number_format((float) $application->monthly_income, 2) : '-',
                'monthly_commitment' => $application->monthly_commitment !== null ? 'RM '.number_format((float) $application->monthly_commitment, 2) : '-',
                'employment_notes' => $application->employment_notes,
                'completed_form_uploaded_at' => $application->completed_form_uploaded_at?->format('d/m/Y H:i'),
                'print_url' => route('member.financing.applications.print', $application),
            ],
            'member' => [
                'full_name' => $application->member?->full_name,
                'member_no' => $application->member?->member_no,
                'identity_no' => $application->member?->identity_no,
                'phone' => $application->member?->phone,
                'email' => $application->member?->email,
                'occupation' => $application->member?->occupation,
                'employer_name' => $application->member?->employer_name,
            ],
            'guarantors' => $application->guarantors->map(fn (FinancingGuarantor $guarantor) => [
                'name' => $guarantor->guarantorMember?->full_name,
                'member_no' => $guarantor->guarantorMember?->member_no,
                'status_label' => $guarantor->status->label(),
                'consent_text' => $guarantor->consent_text,
            ])->all(),
            'documents' => $application->documents->map(fn (FinancingDocument $document) => [
                'label' => $document->label,
                'file_name' => $document->file_name,
            ])->all(),
            'required_documents' => $application->product?->required_documents_json ?? [],
            'product_sections' => [
                'eligibility_terms' => $application->product?->eligibility_terms,
                'product_terms' => $application->product?->product_terms,
                'application_notes' => $application->product?->application_notes,
                'application_instructions' => $application->product?->application_instructions,
            ],
        ];
    }
}
