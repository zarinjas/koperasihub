<?php

namespace App\Http\Controllers\Public;

use App\Models\Member;
use App\Services\MemberCardService;
use Inertia\Inertia;

class MemberVerificationController
{
    public function __construct(
        private readonly MemberCardService $memberCards,
    ) {
    }

    public function show(string $token)
    {
        $member = Member::query()
            ->where('card_public_token', $token)
            ->first();

        if (! $member) {
            return Inertia::render('Public/Pages/Verify/MemberCard', [
                'isValid' => false,
                'verification' => null,
            ])->toResponse(request())->setStatusCode(404);
        }

        return Inertia::render('Public/Pages/Verify/MemberCard', [
            'isValid' => true,
            'verification' => $this->memberCards->publicPayload($member),
        ]);
    }
}
