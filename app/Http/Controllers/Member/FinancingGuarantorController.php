<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\RespondFinancingGuarantorRequest;
use App\Models\FinancingGuarantor;
use App\Services\Files\FinancingFileService;
use App\Services\FinancingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancingGuarantorController extends MemberPortalController
{
    public function __construct(
        private readonly FinancingService $financing,
        private readonly FinancingFileService $files,
    ) {}

    public function index(Request $request): Response
    {
        $member = $this->currentMember($request);

        $requests = $member->financingGuarantorRequests()
            ->with(['application.member', 'application.product'])
            ->latest('created_at')
            ->get()
            ->map(fn (FinancingGuarantor $guarantor) => $this->serializeGuarantorRequest($guarantor))
            ->all();

        return Inertia::render('Member/Pages/Financing/GuarantorRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, FinancingGuarantor $guarantor): Response
    {
        $member = $this->currentMember($request);
        abort_unless($guarantor->guarantor_member_id === $member->id && $guarantor->cooperative_id === $member->cooperative_id, 404);

        $guarantor->load(['application.member', 'application.product']);

        return Inertia::render('Member/Pages/Financing/GuarantorRequests/Show', [
            'requestRecord' => $this->serializeGuarantorRequest($guarantor, includeSignature: true),
            'consentText' => 'Saya bersetuju untuk menjadi penjamin bagi permohonan pembiayaan ini dan mengesahkan bahawa maklumat yang dipaparkan telah disemak.',
        ]);
    }

    public function respond(RespondFinancingGuarantorRequest $request, FinancingGuarantor $guarantor): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($guarantor->guarantor_member_id === $member->id && $guarantor->cooperative_id === $member->cooperative_id, 404);

        $this->financing->respondToGuarantor($guarantor, $request->user(), [
            ...$request->validated(),
            'consent_text' => 'Saya bersetuju untuk menjadi penjamin bagi permohonan pembiayaan ini dan mengesahkan bahawa maklumat yang dipaparkan telah disemak.',
        ]);

        return back()->with('status', 'Maklum balas penjamin berjaya dihantar.');
    }

    private function serializeGuarantorRequest(FinancingGuarantor $guarantor, bool $includeSignature = false): array
    {
        $application = $guarantor->application;

        return [
            'id' => $guarantor->id,
            'status' => $guarantor->status->value,
            'status_label' => $guarantor->status->label(),
            'applicant_name' => $application?->member?->full_name,
            'applicant_member_no' => $application?->member?->member_no,
            'product_name' => $application?->product?->name,
            'amount_requested' => $application ? 'RM '.number_format((float) $application->amount_requested, 2) : null,
            'tenure_months' => $application?->tenure_months,
            'purpose' => $application?->purpose,
            'submitted_at' => $application?->submitted_at?->format('d/m/Y H:i'),
            'responded_at' => $guarantor->responded_at?->format('d/m/Y H:i'),
            'rejection_reason' => $guarantor->rejection_reason,
            'show_url' => route('member.financing.guarantor-requests.show', $guarantor),
            'signature_preview' => $includeSignature ? $this->files->signatureDataUrl($guarantor->signature_path) : null,
        ];
    }
}
