<?php

namespace App\Http\Controllers\Member;

use App\Models\MembershipApplication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipApplicationController extends MemberPortalController
{
    public function index(Request $request): Response
    {
        $member = $this->currentMemberOrNull($request);

        $applications = $member
            ? MembershipApplication::query()
                ->where('cooperative_id', $member->cooperative_id)
                ->where('approved_member_id', $member->id)
                ->latest('submitted_at')
                ->get()
                ->map(function (MembershipApplication $application) use ($request) {
                    $this->authorize('viewMember', $application);

                    return [
                        'id' => $application->id,
                        'application_no' => $application->application_no,
                        'status' => $application->status->value,
                        'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
                        'reviewed_at' => $application->reviewed_at?->format('d/m/Y H:i'),
                        'review_notes' => $application->review_notes,
                        'rejection_reason' => $application->rejection_reason,
                    ];
                })
                ->all()
            : [];

        return Inertia::render('Member/Pages/Applications/Index', [
            'memberLinked' => (bool) $member,
            'applications' => $applications,
        ]);
    }
}
