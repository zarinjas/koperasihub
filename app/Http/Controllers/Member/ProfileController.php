<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\UpdateOwnProfileRequest;
use App\Services\Files\MemberPhotoStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends MemberPortalController
{
    public function __construct(
        private readonly MemberPhotoStorageService $memberPhotos,
    ) {
    }

    public function show(Request $request): Response
    {
        $user = $request->user();
        $member = $this->currentMemberOrNull($request);

        if ($member) {
            $this->authorize('viewPortal', $member);
        }

        return Inertia::render('Member/Pages/Profile', [
            'editing' => $request->boolean('edit') || $request->session()->has('errors'),
            'member' => [
                'id' => $member?->id,
                'is_linked' => (bool) $member,
                'member_no' => $member?->member_no,
                'profile_photo_url' => $this->memberPhotos->url($member?->profile_photo_path),
                'full_name' => $member?->full_name ?? $user->name,
                'identity_no' => $member?->identity_no,
                'email' => $member?->email ?? $user->email,
                'phone' => $member?->phone ?? $user->phone,
                'address' => $member?->address_line_1,
                'occupation' => $member?->occupation,
                'employer_name' => $member?->employer_name,
                'membership_status' => $member?->membership_status->value ?? 'inactive',
                'date_of_birth' => $member?->date_of_birth?->format('d/m/Y'),
                'date_of_birth_input' => $member?->date_of_birth?->format('Y-m-d'),
                'gender' => $member ? $this->genderLabel($member->gender) : null,
                'gender_value' => $member?->gender,
                'joined_at' => $member?->joined_at?->format('d/m/Y'),
            ],
        ]);
    }

    public function update(UpdateOwnProfileRequest $request): RedirectResponse
    {
        $member = $this->currentMember($request);

        $this->authorize('updateProfile', $member);

        $validated = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $this->memberPhotos->store($request->file('profile_photo'), $member);
        }

        $memberUpdates = [];

        if (array_key_exists('full_name', $validated)) {
            $memberUpdates['full_name'] = $validated['full_name'];
        }

        if (array_key_exists('email', $validated)) {
            $memberUpdates['email'] = $validated['email'];
        }

        if (array_key_exists('phone', $validated)) {
            $memberUpdates['phone'] = $validated['phone'] ?: null;
        }

        if (array_key_exists('address', $validated)) {
            $memberUpdates['address_line_1'] = $validated['address'] ?: null;
        }

        if (array_key_exists('date_of_birth', $validated)) {
            $memberUpdates['date_of_birth'] = $validated['date_of_birth'] ?: null;
        }

        if (array_key_exists('gender', $validated)) {
            $memberUpdates['gender'] = $validated['gender'] ?: null;
        }

        if (array_key_exists('occupation', $validated)) {
            $memberUpdates['occupation'] = $validated['occupation'] ?: null;
        }

        if (array_key_exists('employer_name', $validated)) {
            $memberUpdates['employer_name'] = $validated['employer_name'] ?: null;
        }

        if ($memberUpdates !== []) {
            $member->update($memberUpdates);
        }

        if (
            $member->user_id === $request->user()?->id
            && array_intersect(['full_name', 'email', 'phone'], array_keys($validated)) !== []
        ) {
            $request->user()->update([
                'name' => $validated['full_name'] ?? $request->user()->name,
                'email' => $validated['email'] ?? $request->user()->email,
                'phone' => array_key_exists('phone', $validated)
                    ? ($validated['phone'] ?: null)
                    : $request->user()->phone,
            ]);
        }

        return redirect()
            ->route('member.profile', $request->boolean('edit') ? ['edit' => 1] : [])
            ->with('status', 'Profil anda berjaya dikemas kini.');
    }
}