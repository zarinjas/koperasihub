<?php

namespace App\Http\Controllers\Member;

use App\Models\Member;
use App\Services\MemberCardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends MemberPortalController
{
    public function __construct(
        private readonly MemberCardService $memberCards,
    ) {
    }

    public function show(Request $request, ?Member $member = null): Response
    {
        $member ??= $this->currentMember($request);

        $this->authorize('viewCard', $member);

        return Inertia::render('Member/Pages/Card', [
            'card' => $this->memberCards->memberPayload($member),
        ]);
    }
}
