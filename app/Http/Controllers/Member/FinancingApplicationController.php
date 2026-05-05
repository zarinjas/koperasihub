<?php

namespace App\Http\Controllers\Member;

use App\Enums\FinancingApplicationStatus;
use App\Http\Requests\Member\StoreFinancingApplicationRequest;
use App\Http\Requests\Member\UploadFinancingApplicationDocumentRequest;
use App\Models\FinancingApplication;
use App\Models\FinancingDocument;
use App\Models\FinancingProduct;
use App\Services\Files\FinancingFileService;
use App\Services\FinancingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingApplicationController extends MemberPortalController
{
    public function __construct(
        private readonly FinancingService $financing,
        private readonly FinancingFileService $files,
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
            ->with('category')
            ->findOrFail($productId);

        return Inertia::render('Member/Pages/Financing/Applications/Create', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'requires_guarantor' => $product->requires_guarantor,
                'guarantor_count' => $product->guarantor_count,
                'required_documents' => $product->required_documents_json ?? [],
                'min_amount' => $product->min_amount !== null ? (float) $product->min_amount : null,
                'max_amount' => $product->max_amount !== null ? (float) $product->max_amount : null,
                'min_tenure_months' => $product->min_tenure_months,
                'max_tenure_months' => $product->max_tenure_months,
                'category_name' => $product->category?->name,
                'category_rate_image_url' => $product->category?->rate_image_path ? Storage::disk('public')->url($product->category->rate_image_path) : null,
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

        $application->load(['category', 'product', 'documents', 'guarantors.guarantorMember.user', 'histories.actor']);

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
                'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                'approved_amount' => $application->approved_amount !== null ? 'RM '.number_format((float) $application->approved_amount, 2) : null,
                'approved_tenure_months' => $application->approved_tenure_months,
                'documents' => $application->documents->map(fn (FinancingDocument $document) => [
                    'id' => $document->id,
                    'label' => $document->label,
                    'file_name' => $document->file_name,
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
                    'actor_name' => $historyItem->actor?->name,
                    'notes' => $historyItem->notes,
                    'created_at' => $historyItem->created_at?->format('d/m/Y H:i'),
                ])->all(),
            ],
            'canUploadAdditionalDocuments' => $application->status === FinancingApplicationStatus::IncompleteDocuments,
        ]);
    }

    public function uploadDocument(UploadFinancingApplicationDocumentRequest $request, FinancingApplication $application): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);

        $this->financing->uploadAdditionalDocument($application, $request->file('file'), $request->user(), $request->validated('label'));

        return back()->with('status', 'Dokumen tambahan berjaya dimuat naik.');
    }

    public function downloadDocument(Request $request, FinancingApplication $application, FinancingDocument $document): StreamedResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id && $application->cooperative_id === $member->cooperative_id, 404);
        abort_unless($document->financing_application_id === $application->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $this->files->downloadName($document));
    }
}
