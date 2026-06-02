<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreMembershipApplicationRequest;
use App\Services\MembershipApplicationService;
use App\Models\MembershipApplication;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class MembershipApplicationController extends Controller
{
    public function __construct(
        private readonly MembershipApplicationService $applications,
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Public/Pages/MembershipApplications/Create', [
            'genderOptions' => [
                ['value' => '', 'label' => 'Pilih jantina'],
                ['value' => 'male', 'label' => 'Lelaki'],
                ['value' => 'female', 'label' => 'Perempuan'],
            ],
        ]);
    }

    public function store(StoreMembershipApplicationRequest $request): RedirectResponse
    {
        $application = $this->applications->submit(
            $request->validated(),
        );

        return redirect()
            ->route('public.membership.thank-you', $application->application_no);
    }

    public function thankYou(string $applicationNo): Response
    {
        $application = MembershipApplication::query()
            ->where('application_no', $applicationNo)
            ->firstOrFail();

        return Inertia::render('Public/Pages/MembershipApplications/ThankYou', [
            'application_no' => $application->application_no,
            'applicant_name' => $application->full_name,
            'submitted_at' => $application->submitted_at?->format('d/m/Y H:i'),
        ]);
    }
}