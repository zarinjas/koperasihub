<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnsuranApplicationStatus;
use App\Enums\AnsuranDeliveryStatus;
use App\Http\Controllers\Concerns\InteractsWithActiveCooperative;
use App\Http\Controllers\Controller;
use App\Models\AnsuranAgreementTemplate;
use App\Models\AnsuranApplication;
use App\Models\AnsuranApplicationPayment;
use App\Notifications\AnsuranApplicationApproved;
use App\Notifications\AnsuranApplicationRejected;
use App\Services\AnsuranService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnsuranApplicationController extends Controller
{
    use InteractsWithActiveCooperative;

    public function __construct(
        private readonly AnsuranService $ansuranService,
    ) {}

    public function index(Request $request)
    {
        $cooperativeId = $this->activeCooperative()?->id;

        $applications = AnsuranApplication::forCooperative($cooperativeId)
            ->with(['member.user', 'product', 'variant'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('application_no', 'like', "%{$search}%")
                        ->orWhereHas('member.user', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Pages/Ansuran/Applications/Index', [
            'applications' => $applications->through(fn ($app) => [
                'id' => $app->id,
                'application_no' => $app->application_no,
                'member_name' => $app->member->user->name,
                'product_name' => $app->product->name,
                'variant_name' => $app->variant->name,
                'full_price' => (float) $app->full_price,
                'monthly_amount' => (float) $app->monthly_amount,
                'tenure_months' => $app->tenure_months,
                'status' => $app->status->value,
                'status_label' => $app->status->label(),
                'status_color' => $app->status->color(),
                'created_at' => $app->created_at->format('d/m/Y h:i A'),
            ]),
            'filters' => $request->only(['search', 'status']),
            'statuses' => collect(AnsuranApplicationStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function show(AnsuranApplication $application)
    {
        $application->load([
            'member.user', 'member.cooperative',
            'product.category', 'product.images', 'variant',
            'tenureOption', 'guarantors.guarantorMember.user',
            'histories.actor', 'payments',
        ]);

        $cooperativeId = $this->activeCooperative()?->id;

        return Inertia::render('Admin/Pages/Ansuran/Applications/Show', [
            'application' => [
                'id' => $application->id,
                'application_no' => $application->application_no,
                'member' => [
                    'id' => $application->member->id,
                    'member_no' => $application->member->member_no,
                    'name' => $application->member->user->name,
                    'identity_no' => $application->member->identity_no,
                ],
                'product' => [
                    'id' => $application->product->id,
                    'name' => $application->product->name,
                    'category_name' => $application->product->category->name,
                ],
                'variant' => [
                    'id' => $application->variant->id,
                    'name' => $application->variant->name,
                ],
                'financial' => [
                    'full_price' => (float) $application->full_price,
                    'down_payment' => (float) $application->down_payment,
                    'financed_amount' => (float) $application->financed_amount,
                    'interest_rate_percent' => (float) $application->interest_rate_percent,
                    'tenure_months' => $application->tenure_months,
                    'monthly_amount' => (float) $application->monthly_amount,
                    'total_payable' => (float) $application->total_payable,
                ],
                'status' => $application->status->value,
                'status_label' => $application->status->label(),
                'status_color' => $application->status->color(),
                'delivery_method' => $application->delivery_method,
                'delivery_address' => $application->delivery_address,
                'delivery_status' => $application->delivery_status,
                'delivery_tracking_no' => $application->delivery_tracking_no,
                'agreement_content' => $application->agreement_content,
                'signed_agreement_content' => $application->signed_agreement_content,
                'signed_at' => $application->signed_at?->format('d/m/Y h:i A'),
                'notes' => $application->notes,
                'admin_notes' => $application->admin_notes,
                'rejection_reason' => $application->rejection_reason,
                'guarantors' => $application->guarantors->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->guarantorMember->user->name,
                    'status' => $g->status->value,
                    'status_label' => $g->status->label(),
                    'status_color' => $g->status->color(),
                    'rejection_reason' => $g->rejection_reason,
                ]),
                'histories' => $application->histories->map(fn ($h) => [
                    'action' => $h->action,
                    'from_status' => $h->from_status,
                    'to_status' => $h->to_status,
                    'notes' => $h->notes,
                    'actor_name' => $h->actor?->name,
                    'created_at' => $h->created_at->format('d/m/Y h:i A'),
                ]),
                'payments' => $application->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'month_number' => $p->month_number,
                    'amount' => (float) $p->amount,
                    'due_date' => $p->due_date?->format('d/m/Y'),
                    'paid_amount' => (float) $p->paid_amount,
                    'paid_date' => $p->paid_date?->format('d/m/Y'),
                    'status' => $p->status->value,
                    'status_label' => $p->status->label(),
                    'payment_method' => $p->payment_method,
                    'reference_no' => $p->reference_no,
                ]),
                'created_at' => $application->created_at->format('d/m/Y h:i A'),
            ],
            'templates' => $application->cooperative->ansuranAgreementTemplates()->active()->get()->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
            ]),
            'deliveryStatuses' => collect(AnsuranDeliveryStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function markUnderReview(AnsuranApplication $application)
    {
        $this->ansuranService->markUnderReview($application, auth()->user());

        return back()->with('success', 'Permohonan kini dimulakan semakan.');
    }

    public function approve(Request $request, AnsuranApplication $application)
    {
        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        $this->ansuranService->approve($application, auth()->user(), $request->input('notes'));

        $application->member->user->notify(new AnsuranApplicationApproved($application));

        return back()->with('success', 'Permohonan berjaya diluluskan.');
    }

    public function reject(Request $request, AnsuranApplication $application)
    {
        $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->ansuranService->reject($application, auth()->user(), $request->reason);

        $application->member->user->notify(new AnsuranApplicationRejected($application));

        return back()->with('success', 'Permohonan berjaya ditolak.');
    }

    public function cancel(Request $request, AnsuranApplication $application)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $this->ansuranService->cancel($application, auth()->user(), $request->input('reason'));

        return back()->with('success', 'Permohonan berjaya dibatalkan.');
    }

    public function generateAgreement(Request $request, AnsuranApplication $application)
    {
        $request->validate(['template_id' => ['required', 'exists:ansuran_agreement_templates,id']]);

        $this->ansuranService->generateAgreement($application, $request->template_id);

        return back()->with('success', 'Perjanjian berjaya dijana.');
    }

    public function updateDelivery(Request $request, AnsuranApplication $application)
    {
        $request->validate([
            'delivery_status' => ['required', 'string'],
            'delivery_tracking_no' => ['nullable', 'string', 'max:100'],
        ]);

        $this->ansuranService->updateDelivery(
            $application,
            $request->delivery_status,
            $request->delivery_tracking_no
        );

        return back()->with('success', 'Status penghantaran berjaya dikemaskini.');
    }

    public function generatePaymentSchedule(AnsuranApplication $application)
    {
        $this->ansuranService->generatePaymentSchedule($application);

        return back()->with('success', 'Jadual bayaran berjaya dijana.');
    }

    public function recordPayment(Request $request, AnsuranApplication $application)
    {
        $request->validate([
            'payment_id' => ['required', 'exists:ansuran_application_payments,id'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
        ]);

        $payment = $application->payments()->findOrFail($request->payment_id);

        $this->ansuranService->recordPayment(
            $payment,
            (float) $request->paid_amount,
            $request->payment_method,
            $request->reference_no
        );

        return back()->with('success', 'Bayaran berjaya direkodkan.');
    }
}