<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnsuranApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\AnsuranApplication;
use App\Models\Member;
use App\Services\AnsuranService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnsuranApplicationController extends Controller
{
    public function __construct(
        private readonly AnsuranService $ansuranService,
    ) {}

    public function index()
    {
        $cooperativeId = request()->user()->cooperative_id;
        $member = Member::where('cooperative_id', $cooperativeId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $applications = AnsuranApplication::forMember($member->id)
            ->with(['product.category', 'product.images', 'variant'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Member/Pages/Ansuran/MyApplications', [
            'applications' => $applications->through(fn ($app) => [
                'id' => $app->id,
                'application_no' => $app->application_no,
                'product_name' => $app->product->name,
                'variant_name' => $app->variant->name,
                'primary_image_url' => $app->product->primaryImage()?->url(),
                'monthly_amount' => (float) $app->monthly_amount,
                'tenure_months' => $app->tenure_months,
                'status' => $app->status->value,
                'status_label' => $app->status->label(),
                'status_color' => $app->status->color(),
                'created_at' => $app->created_at->format('d/m/Y'),
            ]),
        ]);
    }

    public function show(AnsuranApplication $application)
    {
        $this->authorizeMemberAccess($application);

        $application->load([
            'product.category', 'product.images', 'variant',
            'tenureOption', 'guarantors.guarantorMember.user',
            'histories', 'payments',
        ]);

        return Inertia::render('Member/Pages/Ansuran/ApplicationDetail', [
            'application' => [
                'id' => $application->id,
                'application_no' => $application->application_no,
                'product' => [
                    'id' => $application->product->id,
                    'name' => $application->product->name,
                    'primary_image_url' => $application->product->primaryImage()?->url(),
                    'category_name' => $application->product->category->name,
                ],
                'variant' => [
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
                'notes' => $application->notes,
                'rejection_reason' => $application->rejection_reason,
                'guarantors' => $application->guarantors->map(fn ($g) => [
                    'name' => $g->guarantorMember->user->name,
                    'status_label' => $g->status->label(),
                    'status_color' => $g->status->color(),
                ]),
                'histories' => $application->histories->map(fn ($h) => [
                    'action' => $h->action,
                    'notes' => $h->notes,
                    'created_at' => $h->created_at->format('d/m/Y h:i A'),
                ]),
                'payments' => $application->payments->map(fn ($p) => [
                    'month_number' => $p->month_number,
                    'amount' => (float) $p->amount,
                    'due_date' => $p->due_date?->format('d/m/Y'),
                    'paid_amount' => (float) $p->paid_amount,
                    'status' => $p->status->value,
                    'status_label' => $p->status->label(),
                ]),
                'created_at' => $application->created_at->format('d/m/Y h:i A'),
            ],
        ]);
    }

    public function sign(AnsuranApplication $application)
    {
        $this->authorizeMemberAccess($application);

        if ($application->status !== AnsuranApplicationStatus::AgreementGenerated) {
            return redirect()->route('member.ansuran.applications.show', $application)
                ->with('error', 'Perjanjian belum tersedia untuk ditandatangani.');
        }

        return Inertia::render('Member/Pages/Ansuran/Sign', [
            'application_id' => $application->id,
            'application_no' => $application->application_no,
            'agreement_content' => $application->agreement_content,
        ]);
    }

    public function storeSignature(Request $request, AnsuranApplication $application)
    {
        $this->authorizeMemberAccess($application);

        $request->validate([
            'signed_content' => ['required', 'string'],
        ]);

        $this->ansuranService->sign($application, $request->signed_content);

        return redirect()->route('member.ansuran.applications.show', $application)
            ->with('success', 'Perjanjian berjaya ditandatangani.');
    }

    public function cancel(Request $request, AnsuranApplication $application)
    {
        $this->authorizeMemberAccess($application);

        $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        $this->ansuranService->cancel($application, auth()->user(), $request->input('reason'));

        return redirect()->route('member.ansuran.applications.index')
            ->with('success', 'Permohonan berjaya dibatalkan.');
    }

    private function authorizeMemberAccess(AnsuranApplication $application): void
    {
        $cooperativeId = request()->user()->cooperative_id;
        $member = Member::where('cooperative_id', $cooperativeId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($application->member_id !== $member->id) {
            abort(403);
        }
    }
}
