<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancingApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveFinancingApplicationRequest;
use App\Http\Requests\Admin\CloseFinancingApplicationRequest;
use App\Http\Requests\Admin\MarkFinancingApplicationIncompleteRequest;
use App\Http\Requests\Admin\MarkFinancingApplicationUnderReviewRequest;
use App\Http\Requests\Admin\RejectFinancingApplicationRequest;
use App\Models\FinancingApplication;
use App\Models\FinancingCategory;
use App\Models\FinancingDocument;
use App\Models\FinancingGuarantor;
use App\Models\FinancingProduct;
use App\Services\Files\FinancingFileService;
use App\Services\FinancingService;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingApplicationController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly FinancingService $financing,
        private readonly FinancingFileService $files,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $categoryId = $request->integer('category');
        $productId = $request->integer('product');
        $type = $request->string('type')->toString();

        $applications = $this->financing->adminVisibleApplications($request->user(), $this->settings->activeCooperative()?->id)
            ->with(['member', 'product.category', 'unit', 'reviewer'])
            ->search($search)
            ->when(in_array($status, FinancingApplicationStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($categoryId > 0, fn ($query) => $query->where('financing_category_id', $categoryId))
            ->when($productId > 0, fn ($query) => $query->where('financing_product_id', $productId))
            ->when(in_array($type, ['guaranteed', 'non_guaranteed'], true), fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('type', $type)))
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
                'type' => $type,
            ],
            'applications' => $applications,
            'statusOptions' => $this->statusOptions(includeAll: true),
            'categoryOptions' => $this->categoryOptions(includeAll: true),
            'productOptions' => $this->productOptions(includeAll: true),
            'typeOptions' => [
                ['value' => '', 'label' => 'Semua jenis'],
                ['value' => 'guaranteed', 'label' => 'Berpenjamin'],
                ['value' => 'non_guaranteed', 'label' => 'Tanpa Penjamin'],
            ],
        ]);
    }

    public function show(Request $request, FinancingApplication $application): Response
    {
        $this->ensureVisibleToAdmin($application, $request->user());

        $application->load([
            'member.user',
            'category',
            'product.unit',
            'documents.uploader',
            'guarantors.guarantorMember.user',
            'histories.actor',
            'reviewer',
            'approver',
            'rejector',
        ]);

        return Inertia::render('Admin/Pages/Financing/Applications/Show', [
            'application' => $this->serializeDetail($application),
            'statusOptions' => $this->statusOptions(),
            'canReview' => $request->user()?->can(AccessControl::PERMISSION_REVIEW_FINANCING_APPLICATIONS) ?? false,
            'canApprove' => $request->user()?->can(AccessControl::PERMISSION_APPROVE_FINANCING_APPLICATIONS) ?? false,
        ]);
    }

    public function markUnderReview(MarkFinancingApplicationUnderReviewRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        $this->financing->markUnderReview($application, $request->user(), $request->validated('decision_notes'));

        return back()->with('status', 'Permohonan ditandakan sebagai dalam semakan.');
    }

    public function markIncomplete(MarkFinancingApplicationIncompleteRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        $this->financing->markIncompleteDocuments($application, $request->user(), $request->validated('decision_notes'));

        return back()->with('status', 'Permohonan ditandakan sebagai dokumen tidak lengkap.');
    }

    public function approve(ApproveFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        $this->financing->approve($application, $request->user(), $request->validated());

        return back()->with('status', 'Permohonan pembiayaan berjaya diluluskan.');
    }

    public function reject(RejectFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        $this->financing->reject($application, $request->user(), $request->validated());

        return back()->with('status', 'Permohonan pembiayaan berjaya ditolak.');
    }

    public function cancel(CloseFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        $this->financing->cancel($application, $request->user(), $request->validated('decision_notes'));

        return back()->with('status', 'Permohonan pembiayaan telah dibatalkan.');
    }

    public function close(CloseFinancingApplicationRequest $request, FinancingApplication $application): RedirectResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        $this->financing->close($application, $request->user(), $request->validated('decision_notes'));

        return back()->with('status', 'Permohonan pembiayaan telah ditutup.');
    }

    public function downloadDocument(Request $request, FinancingApplication $application, FinancingDocument $document): StreamedResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        abort_unless($document->financing_application_id === $application->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $this->files->downloadName($document));
    }

    public function downloadGuarantorSignature(Request $request, FinancingApplication $application, FinancingGuarantor $guarantor): StreamedResponse
    {
        $this->ensureVisibleToAdmin($application, $request->user());
        abort_unless($guarantor->financing_application_id === $application->id, 404);
        abort_unless($guarantor->signature_path && Storage::disk('local')->exists($guarantor->signature_path), 404);

        return Storage::disk('local')->download($guarantor->signature_path, 'tandatangan-penjamin-'.$guarantor->id.'.png');
    }

    private function serializeSummary(FinancingApplication $application): array
    {
        return [
            'id' => $application->id,
            'reference_no' => $application->reference_no,
            'member_name' => $application->member?->full_name,
            'member_no' => $application->member?->member_no,
            'category_name' => $application->category?->name,
            'product_name' => $application->product?->name,
            'amount_requested' => 'RM '.number_format((float) $application->amount_requested, 2),
            'status' => $application->status->value,
            'status_label' => $application->status->label(),
            'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
            'reviewer_name' => $application->reviewer?->name,
            'unit_name' => $application->unit?->name,
            'show_url' => route('admin.financing.applications.show', $application),
        ];
    }

    private function serializeDetail(FinancingApplication $application): array
    {
        $history = FinancingApplication::query()
            ->where('cooperative_id', $application->cooperative_id)
            ->where('member_id', $application->member_id)
            ->with(['product', 'reviewer', 'approver', 'rejector'])
            ->latest('submitted_at')
            ->get()
            ->map(fn (FinancingApplication $item) => [
                'id' => $item->id,
                'reference_no' => $item->reference_no,
                'product_name' => $item->product?->name,
                'amount_requested' => 'RM '.number_format((float) $item->amount_requested, 2),
                'status' => $item->status->value,
                'status_label' => $item->status->label(),
                'submitted_at' => $item->submitted_at?->format('d/m/Y H:i'),
                'decision_date' => $item->approved_at?->format('d/m/Y H:i') ?: $item->rejected_at?->format('d/m/Y H:i'),
                'approved_amount' => $item->approved_amount ? 'RM '.number_format((float) $item->approved_amount, 2) : null,
                'officer_name' => $item->approver?->name ?: $item->rejector?->name ?: $item->reviewer?->name,
                'show_url' => route('admin.financing.applications.show', $item),
            ])
            ->all();

        return [
            'id' => $application->id,
            'reference_no' => $application->reference_no,
            'status' => $application->status->value,
            'status_label' => $application->status->label(),
            'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
            'reviewed_at' => $application->reviewed_at?->format('d/m/Y H:i'),
            'approved_at' => $application->approved_at?->format('d/m/Y H:i'),
            'rejected_at' => $application->rejected_at?->format('d/m/Y H:i'),
            'unit_name' => $application->unit?->name ?: $application->product?->unit?->name,
            'category_name' => $application->category?->name,
            'product_name' => $application->product?->name,
            'amount_requested' => (float) $application->amount_requested,
            'amount_requested_label' => 'RM '.number_format((float) $application->amount_requested, 2),
            'tenure_months' => $application->tenure_months,
            'purpose' => $application->purpose,
            'monthly_income' => $application->monthly_income !== null ? 'RM '.number_format((float) $application->monthly_income, 2) : null,
            'monthly_commitment' => $application->monthly_commitment !== null ? 'RM '.number_format((float) $application->monthly_commitment, 2) : null,
            'employment_notes' => $application->employment_notes,
            'approved_amount' => $application->approved_amount !== null ? (float) $application->approved_amount : null,
            'approved_amount_label' => $application->approved_amount !== null ? 'RM '.number_format((float) $application->approved_amount, 2) : null,
            'approved_tenure_months' => $application->approved_tenure_months,
            'decision_notes' => $application->decision_notes,
            'rejection_reason' => $application->rejection_reason,
            'member' => [
                'id' => $application->member?->id,
                'full_name' => $application->member?->full_name,
                'member_no' => $application->member?->member_no,
                'identity_no' => $application->member?->identity_no,
                'phone' => $application->member?->phone,
                'email' => $application->member?->email,
                'occupation' => $application->member?->occupation,
                'employer_name' => $application->member?->employer_name,
                'membership_status' => $application->member?->membership_status?->value,
                'user_name' => $application->member?->user?->name,
                'user_email' => $application->member?->user?->email,
                'show_url' => $application->member ? route('admin.members.show', $application->member) : null,
            ],
            'documents' => $application->documents->map(fn (FinancingDocument $document) => [
                'id' => $document->id,
                'label' => $document->label,
                'file_name' => $document->file_name,
                'file_size_label' => $document->file_size ? number_format($document->file_size / 1024, 0).' KB' : '-',
                'uploaded_by' => $document->uploader?->name,
                'download_url' => route('admin.financing.applications.documents.download', [$application, $document]),
            ])->all(),
            'guarantors' => $application->guarantors->map(fn (FinancingGuarantor $guarantor) => [
                'id' => $guarantor->id,
                'name' => $guarantor->guarantorMember?->full_name,
                'member_no' => $guarantor->guarantorMember?->member_no,
                'employee_no' => $guarantor->guarantorMember?->user?->staff_id,
                'status' => $guarantor->status->value,
                'status_label' => $guarantor->status->label(),
                'consent_text' => $guarantor->consent_text,
                'consented_at' => $guarantor->consented_at?->format('d/m/Y H:i'),
                'responded_at' => $guarantor->responded_at?->format('d/m/Y H:i'),
                'rejection_reason' => $guarantor->rejection_reason,
                'signature_preview' => $this->files->signatureDataUrl($guarantor->signature_path),
                'signature_download_url' => $guarantor->signature_path ? route('admin.financing.applications.guarantors.signature.download', [$application, $guarantor]) : null,
            ])->all(),
            'histories' => $application->histories->map(fn ($historyItem) => [
                'id' => $historyItem->id,
                'action' => $historyItem->action,
                'from_status' => $historyItem->from_status,
                'to_status' => $historyItem->to_status,
                'notes' => $historyItem->notes,
                'actor_name' => $historyItem->actor?->name,
                'created_at' => $historyItem->created_at?->format('d/m/Y H:i'),
            ])->all(),
            'applicant_history' => $history,
        ];
    }

    private function ensureVisibleToAdmin(FinancingApplication $application, $user): void
    {
        $visible = $this->financing->adminVisibleApplications($user, $this->settings->activeCooperative()?->id)
            ->whereKey($application->id)
            ->exists();

        abort_unless($visible, 404);
    }

    private function statusOptions(bool $includeAll = false): array
    {
        $options = collect(FinancingApplicationStatus::cases())
            ->map(fn (FinancingApplicationStatus $status) => ['value' => $status->value, 'label' => $status->label()])
            ->all();

        return $includeAll ? [['value' => '', 'label' => 'Semua status'], ...$options] : $options;
    }

    private function categoryOptions(bool $includeAll = false): array
    {
        $options = FinancingCategory::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => ['value' => $category->id, 'label' => $category->name])
            ->all();

        return $includeAll ? [['value' => '', 'label' => 'Semua kategori'], ...$options] : $options;
    }

    private function productOptions(bool $includeAll = false): array
    {
        $options = FinancingProduct::query()
            ->forCooperative($this->settings->activeCooperative()?->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancingProduct $product) => ['value' => $product->id, 'label' => $product->name])
            ->all();

        return $includeAll ? [['value' => '', 'label' => 'Semua produk'], ...$options] : $options;
    }
}
