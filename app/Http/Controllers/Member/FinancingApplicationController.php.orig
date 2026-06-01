<?php

namespace App\Http\Controllers\Member;

use App\Enums\FinancingApplicationStatus;
use App\Http\Requests\Member\CancelFinancingApplicationRequest;
use App\Http\Requests\Member\StoreFinancingApplicationRequest;
use App\Http\Requests\Member\UploadStampedFinancingFormRequest;
use App\Models\FinancingApplication;
use App\Models\FinancingApplicationDocument;
use App\Models\FinancingApplicationHistory;
use App\Models\FinancingCategory;
use App\Models\FinancingProduct;
use App\Models\FinancingProductField;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingApplicationController extends MemberPortalController
{
    public function __construct(
        private readonly FinancingService $financing,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $member = $this->currentMember($request);
        $statusFilter = $request->string('status')->toString();

        $applications = $member->financingApplications()
            ->with(['product', 'category'])
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->latest('submitted_at')
            ->paginate(15)
            ->through(fn (FinancingApplication $application) => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'category_name' => $application->category?->name,
                'product_name' => $application->product?->name,
                'amount_requested' => (float) $application->amount_requested,
                'tenure_months' => $application->tenure_months,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'status_color' => $application->status->color(),
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'show_url' => route('member.financing.applications.show', $application),
            ]);

        return Inertia::render('Member/Pages/Financing/Applications/Index', [
            'applications' => $applications,
            'statuses' => collect(FinancingApplicationStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ])->values()->all(),
            'filters' => ['status' => $statusFilter],
        ]);
    }

    public function create(Request $request): Response
    {
        $member = $this->currentMember($request);
        $cooperativeId = $this->activeCooperativeId($request);

        $productId = $request->integer('product') ?: $request->integer('product_id');
        $product = null;

        if ($productId) {
            $product = FinancingProduct::query()
                ->where('cooperative_id', $cooperativeId)
                ->where('is_active', true)
                ->with([
                    'sections' => fn ($q) => $q->active()->ordered(),
                    'sections.fields' => fn ($q) => $q->active()->ordered(),
                ])
                ->find($productId);

            if ($product) {
                $product = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'requires_guarantor' => $product->requires_guarantor,
                    'guarantor_count' => $product->guarantor_count,
                    'requires_stamped_upload' => $product->requires_stamped_upload,
                    'stamped_upload_instructions' => $product->stamped_upload_instructions,
                    'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
                    'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
                    'min_tenure_months' => $product->min_tenure_months,
                    'max_tenure_months' => $product->max_tenure_months,
                    'annual_rate_percent' => $product->annual_rate_percent !== null ? (float) $product->annual_rate_percent : null,
                    'rate_image_url' => $product->rateImageUrl(),
                    'rate_note' => $product->rate_note,
                    'form_template_url' => $product->form_template_path ? Storage::disk('public')->url($product->form_template_path) : null,
                    'form_template_name' => $product->form_template_name,
                    'category_id' => $product->financing_category_id,
                    'category_name' => $product->category?->name,
                    'sections' => $product->sections->map(fn ($section) => [
                        'id' => $section->id,
                        'title' => $section->title,
                        'description' => $section->description,
                        'sort_order' => $section->sort_order,
                        'fields' => $section->fields->map(fn (FinancingProductField $field) => [
                            'id' => $field->id,
                            'label' => $field->label,
                            'field_key' => $field->field_key,
                            'type' => $field->type->value,
                            'type_label' => $field->type->label(),
                            'placeholder' => $field->placeholder,
                            'help_text' => $field->help_text,
                            'is_required' => $field->is_required,
                            'options_json' => $field->options_json ?? [],
                            'validation_json' => $field->validation_json ?? [],
                            'settings_json' => $field->settings_json ?? [],
                            'sort_order' => $field->sort_order,
                        ])->values()->all(),
                    ])->values()->all(),
                ];
            }
        }

        $categories = FinancingCategory::query()
            ->forCooperative($cooperativeId)
            ->active()
            ->ordered()
            ->with(['products' => fn ($q) => $q->active()->ordered()])
            ->get()
            ->map(fn (FinancingCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'type' => $category->type->value,
                'type_label' => $category->type->label(),
                'products' => $category->products->map(fn (FinancingProduct $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'min_amount' => $p->min_amount !== null ? (float) $p->min_amount : null,
                    'max_amount' => $p->max_amount !== null ? (float) $p->max_amount : null,
                    'min_tenure_months' => $p->min_tenure_months,
                    'max_tenure_months' => $p->max_tenure_months,
                    'annual_rate_percent' => $p->annual_rate_percent !== null ? (float) $p->annual_rate_percent : null,
                    'requires_guarantor' => $p->requires_guarantor,
                    'guarantor_count' => $p->guarantor_count,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $existingApplications = FinancingApplication::where('member_id', $member->id)
            ->whereNotIn('status', [
                FinancingApplicationStatus::Cancelled->value,
                FinancingApplicationStatus::Rejected->value,
            ])
            ->get(['id', 'financing_product_id', 'status', 'reference_no'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'financing_product_id' => $a->financing_product_id,
                'status' => $a->status->value,
                'reference_no' => $a->reference_no,
            ])
            ->values()
            ->all();

        return Inertia::render('Member/Pages/Financing/Applications/Create', [
            'product' => $product,
            'categories' => $categories,
            'member' => [
                'full_name' => $member->full_name,
                'member_no' => $member->member_no,
                'occupation' => $member->occupation,
                'employer_name' => $member->employer_name,
            ],
            'guarantorSearchUrl' => route('member.financing.guarantor-search'),
            'existingApplications' => $existingApplications,
        ]);
    }

    public function store(StoreFinancingApplicationRequest $request): RedirectResponse
    {
        $member = $this->currentMember($request);
        $cooperativeId = $this->activeCooperativeId($request);
        $product = FinancingProduct::findOrFail($request->financing_product_id);

        // Semak permohonan aktif sedia ada untuk produk ini
        $existing = FinancingApplication::where('member_id', $member->id)
            ->where('financing_product_id', $product->id)
            ->whereNotIn('status', [
                FinancingApplicationStatus::Cancelled->value,
                FinancingApplicationStatus::Rejected->value,
            ])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'financing_product_id' => 'Anda sudah mempunyai permohonan aktif untuk produk ini. Sila batalkan atau tunggu keputusan permohonan sedia ada sebelum membuat permohonan baharu.',
            ])->withInput();
        }

        $application = DB::transaction(function () use ($request, $member, $cooperativeId, $product) {
            $application = FinancingApplication::create([
                'cooperative_id' => $cooperativeId,
                'member_id' => $member->id,
                'financing_category_id' => $request->financing_category_id,
                'financing_product_id' => $product->id,
                'amount_requested' => $request->amount_requested,
                'tenure_months' => $request->tenure_months,
                'purpose' => $request->purpose,
                'monthly_income' => $request->monthly_income,
                'monthly_commitment' => $request->monthly_commitment,
                'employment_notes' => $request->employment_notes,
                'custom_answers_json' => $request->answers ?? [],
                'reference_no' => $this->financing->generateReferenceNo(),
                'status' => FinancingApplicationStatus::PendingUpload,
                'submitted_at' => now(),
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $fieldId => $file) {
                    if (is_array($file)) {
                        foreach ($file as $singleFile) {
                            $field = FinancingProductField::where('financing_product_id', $product->id)->findOrFail($fieldId);
                            $this->financing->uploadDocument($application, $field, $singleFile);
                        }
                    } else {
                        $field = FinancingProductField::where('financing_product_id', $product->id)->findOrFail($fieldId);
                        $this->financing->uploadDocument($application, $field, $file);
                    }
                }
            }

            if (! empty($request->guarantor_member_ids)) {
                $this->financing->createGuarantors($application, $request->guarantor_member_ids);
            }

            FinancingApplicationHistory::create([
                'cooperative_id' => $cooperativeId,
                'financing_application_id' => $application->id,
                'actor_id' => auth()->id(),
                'action' => 'Permohonan dihantar',
                'from_status' => null,
                'to_status' => $application->status->value,
                'created_at' => now(),
            ]);

            return $application;
        });

        return redirect()
            ->route('member.financing.applications.show', $application)
            ->with('status', 'Permohonan pembiayaan berjaya dihantar.');
    }

    public function show(Request $request, FinancingApplication $application): Response
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);

        $application->load([
            'category',
            'product',
            'product.fields' => fn ($q) => $q->active()->ordered(),
            'guarantors.guarantorMember.user',
            'documents',
            'histories.actor',
        ]);

        return Inertia::render('Member/Pages/Financing/Applications/Show', [
            'application' => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'status_color' => $application->status->color(),
                'category_name' => $application->category?->name,
                'product_name' => $application->product?->name,
                'form_template_url' => $application->product?->form_template_path
                    ? Storage::disk('public')->url($application->product->form_template_path)
                    : null,
                'form_template_name' => $application->product?->form_template_name,
                'amount_requested' => 'RM ' . number_format((float) $application->amount_requested, 2),
                'tenure_months' => $application->tenure_months,
                'purpose' => $application->purpose,
                'monthly_income' => $application->monthly_income !== null ? 'RM ' . number_format((float) $application->monthly_income, 2) : null,
                'monthly_commitment' => $application->monthly_commitment !== null ? 'RM ' . number_format((float) $application->monthly_commitment, 2) : null,
                'employment_notes' => $application->employment_notes,
                'custom_answers_json' => $application->custom_answers_json ?? [],
                'decision_notes' => $application->decision_notes,
                'rejection_reason' => $application->rejection_reason,
                'cancellation_reason' => $application->cancellation_reason,
                'cancelled_at' => $application->cancelled_at?->format('d/m/Y H:i'),
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'approved_amount' => $application->approved_amount !== null ? 'RM ' . number_format((float) $application->approved_amount, 2) : null,
                'approved_tenure_months' => $application->approved_tenure_months,
                'print_url' => route('member.financing.applications.print', $application),
                'cancel_url' => route('member.financing.applications.cancel', $application),
                'can_cancel' => in_array($application->status, FinancingApplicationStatus::memberCancellable()),
                'product_fields' => $application->product?->fields->map(fn (FinancingProductField $field) => [
                    'id' => $field->id,
                    'label' => $field->label,
                    'field_key' => $field->field_key,
                    'type' => $field->type->value,
                    'type_label' => $field->type->label(),
                ])->values()->all(),
                'stamped_form' => [
                    'uploaded' => filled($application->stamped_form_path),
                    'file_name' => $application->stamped_form_original_name,
                    'uploaded_at' => $application->stamped_form_uploaded_at?->format('d/m/Y H:i'),
                    'download_url' => $application->stampedFormUrl(),
                    'upload_url' => route('member.financing.applications.stamped-form.store', $application),
                ],
                'documents' => $application->documents->map(fn (FinancingApplicationDocument $document) => [
                    'id' => $document->id,
                    'label' => $document->label,
                    'field_key' => $document->field_key,
                    'file_name' => $document->original_name,
                    'uploaded_at' => $document->created_at?->format('d/m/Y H:i'),
                    'download_url' => route('member.financing.applications.documents.download', [$application, $document]),
                ])->values()->all(),
                'guarantors' => $application->guarantors->map(fn ($guarantor) => [
                    'id' => $guarantor->id,
                    'name' => $guarantor->guarantorMember?->full_name,
                    'member_no' => $guarantor->guarantorMember?->member_no,
                    'status' => $guarantor->status->value,
                    'status_label' => $guarantor->status->label(),
                    'responded_at' => $guarantor->responded_at?->format('d/m/Y H:i'),
                    'rejection_reason' => $guarantor->rejection_reason,
                ])->values()->all(),
                'histories' => $application->histories->map(fn ($history) => [
                    'id' => $history->id,
                    'action' => $history->action,
                    'actor_name' => $history->actor?->name,
                    'notes' => $history->notes,
                    'created_at' => $history->created_at?->format('d/m/Y H:i'),
                ])->values()->all(),
            ],
        ]);
    }

    public function cancel(CancelFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);
        abort_unless(in_array($application->status, FinancingApplicationStatus::memberCancellable()), 403, 'Permohonan ini tidak boleh dibatalkan.');

        $this->financing->cancel($application, auth()->user(), $request->reason);

        return back()->with('status', 'Permohonan pembiayaan anda telah dibatalkan.');
    }

    public function uploadStampedForm(UploadStampedFinancingFormRequest $request, FinancingApplication $application): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);

        $this->financing->uploadStampedForm($application, $request->file('file'));

        if ($request->hasFile('product_form')) {
            $path = $request->file('product_form')->store('financing/stamped-forms/'.$application->id, 'public');
            \App\Models\FinancingApplicationDocument::create([
                'cooperative_id' => $application->cooperative_id,
                'financing_application_id' => $application->id,
                'financing_product_field_id' => null,
                'uploaded_by' => auth()->id(),
                'label' => 'Borang Khas Produk (Bercop)',
                'field_key' => 'product_form_stamped',
                'file_path' => $path,
                'original_name' => $request->file('product_form')->getClientOriginalName(),
                'mime_type' => $request->file('product_form')->getMimeType(),
                'file_size' => $request->file('product_form')->getSize(),
            ]);
        }

        return back()->with('status', 'Dokumen berjaya dimuat naik.');
    }

    public function uploadDocument(Request $request, FinancingApplication $application): JsonResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);

        $validated = $request->validate([
            'field_id' => ['required', 'exists:financing_product_fields,id'],
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $field = FinancingProductField::findOrFail($validated['field_id']);

        $document = $this->financing->uploadDocument($application, $field, $request->file('file'));

        return response()->json([
            'document' => [
                'id' => $document->id,
                'label' => $document->label,
                'field_key' => $document->field_key,
                'file_name' => $document->original_name,
                'uploaded_at' => $document->created_at?->format('d/m/Y H:i'),
                'download_url' => route('member.financing.applications.documents.download', [$application, $document]),
            ],
        ]);
    }

    public function downloadDocument(Request $request, FinancingApplication $application, FinancingApplicationDocument $document): StreamedResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);
        abort_unless($document->financing_application_id === $application->id, 404);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    public function print(Request $request, FinancingApplication $application): Response
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);

        $application->load([
            'member.user',
            'category',
            'product',
            'documents',
            'guarantors.guarantorMember.user',
        ]);

        $formTemplateUrl = $application->product?->form_template_path
            ? Storage::disk('public')->url($application->product->form_template_path)
            : null;

        $appSettings = $this->settings->shared();
        $cooperative = $appSettings['cooperative'] ?? [];
        $contact = $appSettings['contact'] ?? [];

        return Inertia::render('Member/Pages/Financing/Applications/Print', [
            'application' => [
                'id' => $application->id,
                'reference_no' => $application->reference_no,
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'status_color' => $application->status->color(),
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'product_name' => $application->product?->name,
                'category_name' => $application->category?->name,
                'amount_requested' => 'RM ' . number_format((float) $application->amount_requested, 2),
                'tenure_months' => $application->tenure_months,
                'purpose' => $application->purpose,
                'monthly_income' => $application->monthly_income !== null ? 'RM ' . number_format((float) $application->monthly_income, 2) : '-',
                'monthly_commitment' => $application->monthly_commitment !== null ? 'RM ' . number_format((float) $application->monthly_commitment, 2) : '-',
                'custom_answers_json' => $application->custom_answers_json ?? [],
                'print_generated_at' => now()->format('d/m/Y H:i'),
                'form_template_url' => $formTemplateUrl,
                'form_template_name' => $application->product?->form_template_name,
                'requires_stamped_upload' => (bool) $application->product?->requires_stamped_upload,
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
            'guarantors' => $application->guarantors->map(fn ($guarantor) => [
                'name' => $guarantor->guarantorMember?->full_name,
                'member_no' => $guarantor->guarantorMember?->member_no,
                'status_label' => $guarantor->status->label(),
            ])->values()->all(),
        ]);
    }
}
