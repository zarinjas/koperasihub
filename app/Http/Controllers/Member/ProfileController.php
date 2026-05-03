<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\UpdateOwnProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends MemberPortalController
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $member = $this->currentMemberOrNull($request);

        if ($member) {
            $this->authorize('viewPortal', $member);
        }

        return Inertia::render('Member/Pages/Profile', [
            'member' => [
                'id' => $member?->id,
                'is_linked' => (bool) $member,
                'member_no' => $member?->member_no,
                'full_name' => $member?->full_name ?? $user->name,
                'identity_no' => $member?->identity_no,
                'email' => $member?->email ?? $user->email,
                'phone' => $member?->phone ?? $user->phone,
                'address' => $member?->address_line_1,
                'occupation' => $member?->occupation,
                'employer_name' => $member?->employer_name,
                'membership_status' => $member?->membership_status->value ?? 'inactive',
                'date_of_birth' => $member?->date_of_birth?->format('d/m/Y'),
                'gender' => $member ? $this->genderLabel($member->gender) : null,
                'joined_at' => $member?->joined_at?->format('d/m/Y'),
            ],
        ]);
    }

    public function update(UpdateOwnProfileRequest $request): RedirectResponse
    {
        $member = $this->currentMember($request);

        $this->authorize('updateProfile', $member);

        $validated = $request->validated();

        $member->update([
            'phone' => $validated['phone'] ?: null,
            'address_line_1' => $validated['address'] ?: null,
            'occupation' => $validated['occupation'] ?: null,
            'employer_name' => $validated['employer_name'] ?: null,
        ]);

        if ($member->user_id === $request->user()?->id) {
            $request->user()->update([
                'phone' => $validated['phone'] ?: null,
            ]);
        }

        return back()->with('status', 'Profil anda berjaya dikemas kini.');
    }
}
