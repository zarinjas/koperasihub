<?php

namespace App\Http\Controllers\Member;

use App\Enums\FinancingGuarantorStatus;
use App\Models\FinancingApplicationDocument;
use App\Models\FinancingGuarantor;
use App\Services\FinancingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FinancingGuarantorController extends MemberPortalController
{
    public function __construct(
        private readonly FinancingService $financing,
    ) {}

    public function index(Request $request): Response
    {
        $member = $this->currentMember($request);

        $requests = $member->financingGuarantorRequests()
            ->with(['application.member.user', 'application.product', 'application.category'])
            ->latest('created_at')
            ->get()
            ->map(fn (FinancingGuarantor $guarantor) => [
                'id' => $guarantor->id,
                'created_at' => $guarantor->created_at?->toIso8601String(),
                'status' => $guarantor->status->value,
                'status_label' => $guarantor->status->label(),
                'responded_at' => $guarantor->responded_at?->toIso8601String(),
                'rejection_reason' => $guarantor->rejection_reason,
                'application' => $guarantor->application ? [
                    'id' => $guarantor->application->id,
                    'reference_no' => $guarantor->application->reference_no,
                    'amount_requested' => (float) $guarantor->application->amount_requested,
                    'tenure_months' => $guarantor->application->tenure_months,
                    'purpose' => $guarantor->application->purpose,
                    'submitted_at' => $guarantor->application->submitted_at?->toIso8601String(),
                    'member' => $guarantor->application->member ? [
                        'full_name' => $guarantor->application->member->full_name,
                        'member_no' => $guarantor->application->member->member_no,
                        'phone' => $guarantor->application->member->phone,
                        'email' => $guarantor->application->member->email,
                    ] : null,
                    'product' => $guarantor->application->product ? [
                        'id' => $guarantor->application->product->id,
                        'name' => $guarantor->application->product->name,
                        'description' => $guarantor->application->product->description,
                        'annual_rate_percent' => $guarantor->application->product->annual_rate_percent !== null ? (float) $guarantor->application->product->annual_rate_percent : null,
                    ] : null,
                    'category' => $guarantor->application->category ? [
                        'id' => $guarantor->application->category->id,
                        'name' => $guarantor->application->category->name,
                    ] : null,
                ] : null,
                'show_url' => route('member.financing.guarantor-requests.show', $guarantor),
            ])
            ->values()
            ->all();

        return Inertia::render('Member/Pages/Financing/GuarantorRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, FinancingGuarantor $guarantor): Response
    {
        $member = $this->currentMember($request);
        abort_unless($guarantor->guarantor_member_id === $member->id, 404);

        $guarantor->load([
            'application.member.user',
            'application.product',
            'application.category',
            'application.documents',
        ]);

        $application = $guarantor->application;

        return Inertia::render('Member/Pages/Financing/GuarantorRequests/Show', [
            'guarantor' => [
                'id' => $guarantor->id,
                'created_at' => $guarantor->created_at?->toIso8601String(),
                'status' => $guarantor->status->value,
                'status_label' => $guarantor->status->label(),
                'responded_at' => $guarantor->responded_at?->toIso8601String(),
                'rejection_reason' => $guarantor->rejection_reason,
                'signature_preview' => $this->signatureDataUrl($guarantor->signature_path),
                'application' => $application ? [
                    'id' => $application->id,
                    'reference_no' => $application->reference_no,
                    'amount_requested' => (float) $application->amount_requested,
                    'tenure_months' => $application->tenure_months,
                    'purpose' => $application->purpose,
                    'custom_answers_json' => $application->custom_answers_json ?? [],
                    'submitted_at' => $application->submitted_at?->toIso8601String(),
                    'member' => $application->member ? [
                        'full_name' => $application->member->full_name,
                        'member_no' => $application->member->member_no,
                        'phone' => $application->member->phone,
                        'email' => $application->member->email,
                        'position' => $application->member->position,
                        'employer' => $application->member->employer,
                    ] : null,
                    'product' => $application->product ? [
                        'id' => $application->product->id,
                        'name' => $application->product->name,
                        'description' => $application->product->description,
                        'annual_rate_percent' => $application->product->annual_rate_percent !== null ? (float) $application->product->annual_rate_percent : null,
                        'rate_tiers_json' => $application->product->rate_tiers_json ?? [],
                        'rate_note' => $application->product->rate_note,
                    ] : null,
                    'category' => $application->category ? [
                        'id' => $application->category->id,
                        'name' => $application->category->name,
                    ] : null,
                    'documents' => $application->documents?->map(fn (FinancingApplicationDocument $doc) => [
                        'id' => $doc->id,
                        'label' => $doc->label,
                        'original_name' => $doc->original_name,
                        'download_url' => route('member.financing.applications.documents.download', [
                            'application' => $application,
                            'document' => $doc,
                        ]),
                    ])->values()->all() ?? [],
                ] : null,
            ],
            'respondUrl' => route('member.financing.guarantor-requests.respond', $guarantor),
            'consentText' => 'Saya bersetuju untuk menjadi penjamin bagi permohonan pembiayaan ini dan mengesahkan bahawa maklumat yang dipaparkan telah disemak.',
            'existing_signature' => $member->digital_signature,
        ]);
    }

    public function respond(Request $request, FinancingGuarantor $guarantor): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($guarantor->guarantor_member_id === $member->id, 404);
        abort_unless($guarantor->status === FinancingGuarantorStatus::Pending, 403, 'Maklum balas telah dihantar sebelum ini.');

        $validated = $request->validate([
            'action' => ['required', 'in:accepted,rejected'],
            'signature' => ['nullable', 'string'],
            'use_existing_signature' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['action'] === 'accepted') {
            $signaturePath = null;

            if (! empty($validated['signature'])) {
                $signaturePath = $this->saveSignatureFromBase64($validated['signature']);
            } elseif (! empty($validated['use_existing_signature']) && $member->digital_signature) {
                $signaturePath = $this->saveSignatureFromBase64($member->digital_signature);
            }

            $this->financing->acceptGuarantor($guarantor, $signaturePath);

            return redirect()
                ->route('member.financing.guarantor-requests.index')
                ->with('status', 'Anda telah bersetuju menjadi penjamin. Terima kasih.');
        }

        $this->financing->rejectGuarantor($guarantor, $validated['reason'] ?? null);

        return redirect()
            ->route('member.financing.guarantor-requests.index')
            ->with('status', 'Maklum balas penolakan penjamin telah dihantar.');
    }

    private function saveSignatureFromBase64(string $base64Data): ?string
    {
        $decoded = $this->decodeBase64Image($base64Data);
        if ($decoded === null) {
            return null;
        }

        $filename = 'financing/signatures/' . uniqid('sig_', true) . '.png';
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }

    private function decodeBase64Image(string $data): ?string
    {
        if (str_contains($data, ';base64,')) {
            $data = explode(';base64,', $data)[1];
        } elseif (str_contains($data, 'base64,')) {
            $data = explode('base64,', $data)[1];
        }

        $decoded = base64_decode($data, true);

        return $decoded !== false ? $decoded : null;
    }

    private function signatureDataUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        $content = base64_encode(Storage::disk('public')->get($path));

        return "data:{$mime};base64,{$content}";
    }
}