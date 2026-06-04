<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingApplicationStatus;
use App\Enums\FinancingGuarantorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveFinancingApplicationRequest;
use App\Http\Requests\Admin\RejectFinancingApplicationRequest;
use App\Http\Requests\Admin\ReviewFinancingApplicationRequest;
use App\Models\FinancingApplication;
use App\Models\FinancingApplicationDocument;
use App\Models\FinancingCategory;
use App\Models\FinancingGeneratedDocument;
use App\Models\FinancingProduct;
use App\Services\Financing\FinancingDocumentPackageService;
use App\Services\Financing\FinancingPrintPackageService;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingApplicationController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly FinancingPrintPackageService $printPackage,
        private readonly FinancingService $financing,
        private readonly FinancingDocumentPackageService $documentPackage,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $categoryId = $request->integer('category');
        $productId = $request->integer('product');

        $applications = FinancingApplication::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->with(['member.user', 'product.fields', 'category', 'guarantors', 'documents'])
            ->when($search !== '', fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('member', fn ($mq) => $mq->where('full_name', 'like', "%{$search}%"));
            }))
            ->when(in_array($status, FinancingApplicationStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($categoryId > 0, fn ($query) => $query->where('financing_category_id', $categoryId))
            ->when($productId > 0, fn ($query) => $query->where('financing_product_id', $productId))
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (FinancingApplication $application) => $this->serializeSummary($application));

        return Inertia::render('Admin/Pages/Financing/Applications/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $categoryId ?: '',
                'product' => $productId ?: '',
            ],
            'applications' => $applications,
            'categories' => $this->categoryOptions(),
            'statuses' => $this->statusOptions(),
            'productOptions' => $this->productOptions(),
        ]);
    }

    public function show(Request $request, FinancingApplication $application): Response
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $application->load([
            'member.user',
            'product.fields',
            'category',
            'guarantors.guarantorMember.user',
            'documents',
            'generatedDocuments',
            'histories.actor',
            'approver',
            'rejecter',
            'snapshot',
        ]);

        if ($application->generatedDocuments->isEmpty()) {
            $this->documentPackage->createForApplication($application);
            $application->load('generatedDocuments');
        }

        return Inertia::render('Admin/Pages/Financing/Applications/Show', [
            'application' => $this->serializeDetail($application),
        ]);
    }

    public function print(Request $request, FinancingApplication $application): Response
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $application->load([
            'member.user',
            'product.fields',
            'category',
            'guarantors.guarantorMember.user',
            'documents',
            'generatedDocuments',
            'snapshot',
        ]);

        return Inertia::render('Admin/Pages/Financing/Applications/Print', [
            'application' => $this->serializeDetail($application),
        ]);
    }

    public function package(FinancingApplication $application): BinaryFileResponse
    {
        $this->ensureVisibleToAdmin($application, request()->user());

        $package = $this->printPackage->package($application);

        return response()->download(
            $package['zip_path'],
            $package['zip_name'],
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    public function markInReview(ReviewFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $this->financing->markInReview($application, $request->user());

        return back()->with('status', 'Permohonan ditandakan sebagai dalam semakan.');
    }

    public function markIncomplete(ReviewFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $this->financing->markIncomplete($application, $request->user(), $request->notes);

        return back()->with('status', 'Permohonan ditandakan sebagai dokumen tidak lengkap.');
    }

    public function approve(ApproveFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $this->financing->approve(
            $application,
            $request->user(),
            $request->approved_amount,
            $request->approved_tenure_months,
            $request->notes,
        );

        return back()->with('status', 'Permohonan pembiayaan berjaya diluluskan.');
    }

    public function reject(RejectFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $this->financing->reject($application, $request->user(), $request->reason);

        return back()->with('status', 'Permohonan pembiayaan berjaya ditolak.');
    }

    public function cancel(ReviewFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $this->financing->cancel($application, $request->user(), $request->notes);

        return back()->with('status', 'Permohonan pembiayaan telah dibatalkan.');
    }

    public function destroy(Request $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $this->financing->delete($application);

        return redirect()->route('admin.financing.applications.index')
            ->with('status', 'Permohonan pembiayaan berjaya dipadam.');
    }

    public function downloadDocument(FinancingApplication $application, FinancingApplicationDocument $document): StreamedResponse
    {
        abort_unless($document->financing_application_id === $application->id, 404);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_name ?: basename($document->file_path)
        );
    }

    public function downloadStampedForm(FinancingApplication $application): StreamedResponse
    {
        abort_unless($application->stamped_form_path && Storage::disk('public')->exists($application->stamped_form_path), 404);

        return Storage::disk('public')->download(
            $application->stamped_form_path,
            $application->stamped_form_original_name ?: basename($application->stamped_form_path)
        );
    }

    private function serializeSummary(FinancingApplication $application): array
    {
        $guarantorCount = $application->guarantors_count ?? $application->guarantors?->count() ?? 0;
        $guarantorAccepted = $application->guarantors?->filter(fn ($g) => $g->status === FinancingGuarantorStatus::Accepted)?->count() ?? 0;
        $guarantorPending = $application->guarantors?->filter(fn ($g) => $g->status === FinancingGuarantorStatus::Pending)?->count() ?? 0;
        $documentTotal = $application->documents_count ?? $application->documents?->count() ?? 0;
        $requiredFields = $application->product?->fields?->count() ?? 0;

        return [
            'id' => $application->id,
            'reference_no' => $application->reference_no,
            'member_name' => $application->member?->full_name,
            'member_no' => $application->member?->member_no,
            'product' => $application->product ? [
                'id' => $application->product->id,
                'name' => $application->product->name,
            ] : null,
            'category' => $application->category ? [
                'id' => $application->category->id,
                'name' => $application->category->name,
            ] : null,
            'amount_requested' => $application->amount_requested !== null ? (float) $application->amount_requested : null,
            'amount_requested_label' => $application->amount_requested !== null ? 'RM '.number_format((float) $application->amount_requested, 2) : null,
            'tenure_months' => $application->tenure_months,
            'status' => [
                'value' => $application->status->value,
                'label' => $application->status->label(),
                'color' => $application->status->color(),
            ],
            'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
            'created_at' => $application->created_at?->format('d/m/Y H:i'),
            'guarantor_summary' => $guarantorCount > 0
                ? $guarantorAccepted.'/'.$guarantorCount.' bersetuju'
                : null,
            'guarantor_pending' => $guarantorPending,
            'document_completion' => $requiredFields > 0
                ? $documentTotal.'/'.$requiredFields
                : ($documentTotal > 0 ? $documentTotal.' dimuat naik' : null),
            'show_url' => route('admin.financing.applications.show', $application),
            'print_url' => route('admin.financing.applications.print', $application),
        ];
    }

    private function serializeDetail(FinancingApplication $application): array
    {
        return [
            'id' => $application->id,
            'reference_no' => $application->reference_no,
            'status' => [
                'value' => $application->status->value,
                'label' => $application->status->label(),
                'color' => $application->status->color(),
            ],
            'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
            'reviewed_at' => $application->reviewed_at?->format('d/m/Y H:i'),
            'approved_at' => $application->approved_at?->format('d/m/Y H:i'),
            'rejected_at' => $application->rejected_at?->format('d/m/Y H:i'),
            'cancelled_at' => $application->cancelled_at?->format('d/m/Y H:i'),
            'print_url' => route('admin.financing.applications.print', $application),
            'category_name' => $application->category?->name,
            'product_name' => $application->product?->name,
            'amount_requested' => $application->amount_requested !== null ? (float) $application->amount_requested : null,
            'amount_requested_label' => $application->amount_requested !== null ? 'RM '.number_format((float) $application->amount_requested, 2) : null,
            'tenure_months' => $application->tenure_months,
            'purpose' => $application->purpose,
            'monthly_income' => $application->monthly_income !== null ? (float) $application->monthly_income : null,
            'monthly_income_label' => $application->monthly_income !== null ? 'RM '.number_format((float) $application->monthly_income, 2) : null,
            'monthly_commitment' => $application->monthly_commitment !== null ? (float) $application->monthly_commitment : null,
            'monthly_commitment_label' => $application->monthly_commitment !== null ? 'RM '.number_format((float) $application->monthly_commitment, 2) : null,
            'employment_notes' => $application->employment_notes,
            'custom_sections' => collect($application->snapshot?->sections_snapshot_json ?? [])
                ->map(function ($section) use ($application) {
                    $answers = $application->custom_answers_json ?? [];
                    $nonTextFieldTypes = ['address_my', 'address_spouse', 'address_beneficiary', 'digital_signature', 'file', 'signature_block', 'image', 'pdf_document', 'document_checklist'];
                    return [
                        'title' => $section['title'],
                        'fields' => collect($section['fields'] ?? [])
                            ->filter(function ($field) use ($nonTextFieldTypes) {
                                if (in_array($field['type'], $nonTextFieldTypes, true)) return false;
                                $key = $field['field_key'];
                                if (str_ends_with($key, '_line1') || str_ends_with($key, '_line2') || str_ends_with($key, '_postcode') || str_ends_with($key, '_city') || str_ends_with($key, '_state')) return false;
                                return true;
                            })
                            ->map(function ($field) use ($answers) {
                                return [
                                    'label' => $field['label'] ?? $field['field_key'],
                                    'value' => $answers[$field['field_key']] ?? null,
                                    'field_key' => $field['field_key'],
                                ];
                            })
                            ->filter(fn ($f) => $f['value'] !== null && $f['value'] !== '')
                            ->values()
                            ->all(),
                    ];
                })
                ->filter(fn ($s) => count($s['fields']) > 0)
                ->values()
                ->all(),
            'custom_answers_json' => $application->custom_answers_json ?? [],
            'approved_amount' => $application->approved_amount !== null ? (float) $application->approved_amount : null,
            'approved_amount_label' => $application->approved_amount !== null ? 'RM '.number_format((float) $application->approved_amount, 2) : null,
            'approved_tenure_months' => $application->approved_tenure_months,
            'decision_notes' => $application->decision_notes,
            'rejection_reason' => $application->rejection_reason,
            'stamped_form_path' => $application->stamped_form_path,
            'stamped_form_original_name' => $application->stamped_form_original_name,
            'stamped_form_uploaded_at' => $application->stamped_form_uploaded_at?->format('d/m/Y H:i'),
            'stamped_form_download_url' => $application->stamped_form_path
                ? route('admin.financing.applications.stamped-form.download', $application)
                : null,
            'member' => [
                'id' => $application->member?->id,
                'full_name' => $application->member?->full_name,
                'member_no' => $application->member?->member_no,
                'identity_no' => $application->member?->identity_no,
                'phone' => $application->member?->phone,
                'email' => $application->member?->email,
                'user_name' => $application->member?->user?->name,
                'user_email' => $application->member?->user?->email,
                'digital_signature' => $application->member?->digital_signature,
            ],
            'product' => [
                'id' => $application->product?->id,
                'name' => $application->product?->name,
                'fields' => $application->product?->fields?->map(fn ($field) => [
                    'id' => $field->id,
                    'label' => $field->label,
                    'field_key' => $field->field_key,
                    'type' => $field->type->value,
                    'settings_json' => $field->settings_json ?? [],
                ])->all() ?? [],
            ],
            'has_snapshot' => $application->snapshot !== null,
            'sections_snapshot' => $application->snapshot?->sections_snapshot_json ?? [],
            'product_snapshot' => $application->snapshot?->product_snapshot_json ?? [],
            'resolved_configuration' => $application->snapshot?->resolved_configuration_json ?? [],
            'guarantors' => $application->guarantors?->map(fn ($guarantor) => [
                'id' => $guarantor->id,
                'name' => $guarantor->guarantorMember?->full_name,
                'member_no' => $guarantor->guarantorMember?->member_no,
                'identity_no' => $guarantor->guarantorMember?->identity_no,
                'phone' => $guarantor->guarantorMember?->phone,
                'position' => $guarantor->guarantorMember?->position,
                'employer' => $guarantor->guarantorMember?->employer,
                'status' => $guarantor->status->value,
                'status_label' => $guarantor->status->label(),
                'consent_text' => $guarantor->consent_text,
                'consented_at' => $guarantor->consented_at?->format('d/m/Y H:i'),
                'responded_at' => $guarantor->responded_at?->format('d/m/Y H:i'),
                'rejection_reason' => $guarantor->rejection_reason,
                'signature_data_url' => $guarantor->signature_path && Storage::disk('public')->exists($guarantor->signature_path)
                    ? 'data:'.(Storage::disk('public')->mimeType($guarantor->signature_path) ?: 'image/png').';base64,'.base64_encode(Storage::disk('public')->get($guarantor->signature_path))
                    : null,
                'address' => collect([
                    $guarantor->guarantorMember?->address_line_1,
                    $guarantor->guarantorMember?->address_line_2,
                    $guarantor->guarantorMember?->city,
                    $guarantor->guarantorMember?->state,
                    $guarantor->guarantorMember?->postcode,
                ])->filter()->implode(', '),
            ])->all() ?? [],
            'documents' => $application->documents?->map(fn (FinancingApplicationDocument $document) => [
                'id' => $document->id,
                'label' => $document->label,
                'file_name' => $document->original_name,
                'uploaded_at' => $document->created_at?->format('d/m/Y H:i'),
                'download_url' => route('admin.financing.applications.documents.download', [
                    'application' => $application,
                    'document' => $document,
                ]),
            ])->all() ?? [],
            'generated_documents' => $application->generatedDocuments?->map(fn (FinancingGeneratedDocument $document) => [
                'id' => $document->id,
                'name' => $document->document_name,
                'code' => $document->document_code,
                'type' => $document->document_type,
                'status' => $document->status,
                'requires_upload' => $document->requires_upload,
                'requires_verification' => $document->requires_verification,
                'generated' => filled($document->generated_path),
                'uploaded' => filled($document->uploaded_path),
                'uploaded_file_name' => $document->uploaded_original_name,
                'uploaded_at' => $document->uploaded_at?->format('d/m/Y H:i'),
                'verified_at' => $document->verified_at?->format('d/m/Y H:i'),
                'rejection_reason' => $document->rejection_reason,
                'download_url' => route('admin.financing.applications.generated-documents.download', [$application, $document]),
                'uploaded_download_url' => $document->uploaded_path
                    ? route('admin.financing.applications.generated-documents.uploaded', [$application, $document])
                    : null,
                'verify_url' => route('admin.financing.applications.generated-documents.verify', [$application, $document]),
                'reject_url' => route('admin.financing.applications.generated-documents.reject', [$application, $document]),
            ])->all() ?? [],
            'histories' => $application->histories?->map(fn ($history) => [
                'id' => $history->id,
                'action' => $history->action,
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'notes' => $history->notes,
                'actor_name' => $history->actor?->name,
                'created_at' => $history->created_at?->format('d/m/Y H:i'),
            ])->all() ?? [],
        ];
    }

    private function categoryOptions(): array
    {
        return FinancingCategory::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->latest()
            ->get()
            ->map(fn (FinancingCategory $category) => [
                'value' => $category->id,
                'label' => $category->name,
            ])
            ->all();
    }

    private function statusOptions(): array
    {
        return collect(FinancingApplicationStatus::cases())
            ->map(fn (FinancingApplicationStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();
    }

    private function productOptions(): array
    {
        return FinancingProduct::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->active()
            ->latest()
            ->get()
            ->map(fn (FinancingProduct $product) => [
                'value' => $product->id,
                'label' => $product->name,
            ])
            ->all();
    }

    private function ensureVisibleToAdmin(FinancingApplication $application, $user): void
    {
        $visible = FinancingApplication::query()
            ->where('cooperative_id', $this->settings->activeCooperative()?->id)
            ->whereKey($application->id)
            ->exists();

        abort_unless($visible, 404);
    }
}