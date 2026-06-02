<?php

namespace App\Http\Controllers\Member;

use App\Enums\AnsuranApplicationStatus;
use App\Enums\AnsuranGuarantorStatus;
use App\Http\Controllers\Controller;
use App\Models\AnsuranApplication;
use App\Models\AnsuranApplicationGuarantor;
use App\Models\Member;
use App\Notifications\AnsuranApplicationSubmitted;
use App\Notifications\AnsuranGuarantorsApproved;
use App\Services\AnsuranService;
use App\Services\NotificationRoutingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnsuranGuarantorController extends Controller
{
    public function __construct(
        private readonly AnsuranService $ansuranService,
        private readonly NotificationRoutingService $notificationRouter,
    ) {}

    public function index()
    {
        $cooperativeId = request()->user()->cooperative_id;
        $member = Member::where('cooperative_id', $cooperativeId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $requests = AnsuranApplicationGuarantor::where('guarantor_member_id', $member->id)
            ->with(['application.product', 'application.variant', 'application.member.user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'status' => $g->status->value,
                'status_label' => $g->status->label(),
                'status_color' => $g->status->color(),
                'rejection_reason' => $g->rejection_reason,
                'application' => [
                    'id' => $g->application->id,
                    'application_no' => $g->application->application_no,
                    'member_name' => $g->application->member->user->name,
                    'product_name' => $g->application->product->name,
                    'variant_name' => $g->application->variant->name,
                    'full_price' => (float) $g->application->full_price,
                    'monthly_amount' => (float) $g->application->monthly_amount,
                    'tenure_months' => $g->application->tenure_months,
                ],
            ]);

        return Inertia::render('Member/Pages/Ansuran/GuarantorRequests', [
            'requests' => $requests,
        ]);
    }

    public function respond(Request $request, AnsuranApplicationGuarantor $guarantor)
    {
        $this->authorizeAccess($guarantor);

        $request->validate([
            'action' => ['required', 'in:accept,reject'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->action === 'accept') {
            $this->ansuranService->acceptGuarantor($guarantor);

            $application = $guarantor->application;

            if ($application->status === AnsuranApplicationStatus::Pending) {
                $application->member->user->notify(new AnsuranGuarantorsApproved($application));

                $recipients = $this->notificationRouter->recipients($application->unit_id, $application->cooperative_id);

                foreach ($recipients as $recipient) {
                    $recipient->notify(new AnsuranApplicationSubmitted($application));
                }
            }

            return back()->with('success', 'Anda telah bersetuju menjadi penjamin.');
        }

        $this->ansuranService->rejectGuarantor($guarantor, $request->input('reason'));

        return back()->with('success', 'Anda telah menolak permintaan penjamin.');
    }

    public function memberSearch(Request $request)
    {
        $cooperativeId = request()->user()->cooperative_id;
        $search = $request->input('q', '');

        $members = Member::where('cooperative_id', $cooperativeId)
            ->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->with('user')
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->user->name,
                'member_no' => $m->member_no,
            ]);

        return response()->json($members);
    }

    private function authorizeAccess(AnsuranApplicationGuarantor $guarantor): void
    {
        $cooperativeId = request()->user()->cooperative_id;
        $member = Member::where('cooperative_id', $cooperativeId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($guarantor->guarantor_member_id !== $member->id) {
            abort(403);
        }
    }
}