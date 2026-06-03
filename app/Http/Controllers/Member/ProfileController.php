<?php

namespace App\Http\Controllers\Member;

use App\Http\Requests\Member\UpdateOwnProfileRequest;
use App\Services\Files\MemberPhotoStorageService;
use Illuminate\Http\JsonResponse;
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

    private function checkAndMarkOnboardingComplete(\App\Models\Member $member): void
    {
        if ($member->onboarding_completed_at) {
            return;
        }

        if ($member->address_line_1 && $member->phone) {
            $member->update(['onboarding_completed_at' => now()]);
        }
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
                'address_line_1' => $member?->address_line_1,
                'address_line_2' => $member?->address_line_2,
                'city' => $member?->city,
                'state' => $member?->state,
                'postcode' => $member?->postcode,
                'position' => $member?->position,
                'department' => $member?->department,
                'employer' => $member?->employer,
                'membership_status' => $member?->membership_status->value ?? 'inactive',
                'date_of_birth' => $member?->date_of_birth?->format('d/m/Y'),
                'date_of_birth_input' => $member?->date_of_birth?->format('Y-m-d'),
                'gender' => $member ? $this->genderLabel($member->gender) : null,
                'gender_value' => $member?->gender,
                'marital_status' => $member?->marital_status,
                'marital_status_label' => $member ? $this->maritalStatusLabel($member->marital_status) : null,
                'salary' => $member?->salary,
                'bank' => $member?->bank,
                'bank_account' => $member?->bank_account,
                'monthly_fee' => $member?->monthly_fee,
                'monthly_deduction' => $member?->monthly_deduction,
                'next_of_kin_name' => $member?->next_of_kin_name,
                'next_of_kin_relation' => $member?->next_of_kin_relation,
                'next_of_kin_phone' => $member?->next_of_kin_phone,
                'next_of_kin_address' => $member?->next_of_kin_address,
                'spouse_name' => $member?->spouse_name,
                'spouse_phone' => $member?->spouse_phone,
                'spouse_address' => $member?->spouse_address,
                'digital_signature' => $member?->digital_signature,
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

        if (array_key_exists('address', $validated) && ! array_key_exists('address_line_1', $validated)) {
            $validated['address_line_1'] = $validated['address'];
        }

        if (array_key_exists('address_line_1', $validated)) {
            $memberUpdates['address_line_1'] = $validated['address_line_1'] ?: null;
        }

        if (array_key_exists('address_line_2', $validated)) {
            $memberUpdates['address_line_2'] = $validated['address_line_2'] ?: null;
        }

        if (array_key_exists('city', $validated)) {
            $memberUpdates['city'] = $validated['city'] ?: null;
        }

        if (array_key_exists('state', $validated)) {
            $memberUpdates['state'] = $validated['state'] ?: null;
        }

        if (array_key_exists('postcode', $validated)) {
            $memberUpdates['postcode'] = $validated['postcode'] ?: null;
        }

        if (array_key_exists('date_of_birth', $validated)) {
            $memberUpdates['date_of_birth'] = $validated['date_of_birth'] ?: null;
        }

        if (array_key_exists('gender', $validated)) {
            $memberUpdates['gender'] = $validated['gender'] ?: null;
        }

        if (array_key_exists('marital_status', $validated)) {
            $memberUpdates['marital_status'] = $validated['marital_status'] ?: null;
        }

        if (array_key_exists('occupation', $validated) && ! array_key_exists('position', $validated)) {
            $validated['position'] = $validated['occupation'];
        }

        if (array_key_exists('position', $validated)) {
            $memberUpdates['position'] = $validated['position'] ?: null;
        }

        if (array_key_exists('department', $validated)) {
            $memberUpdates['department'] = $validated['department'] ?: null;
        }

        if (array_key_exists('employer_name', $validated) && ! array_key_exists('employer', $validated)) {
            $validated['employer'] = $validated['employer_name'];
        }

        if (array_key_exists('employer', $validated)) {
            $memberUpdates['employer'] = $validated['employer'] ?: null;
        }

        if (array_key_exists('salary', $validated)) {
            $memberUpdates['salary'] = $validated['salary'];
        }

        if (array_key_exists('bank', $validated)) {
            $memberUpdates['bank'] = $validated['bank'] ?: null;
        }

        if (array_key_exists('bank_account', $validated)) {
            $memberUpdates['bank_account'] = $validated['bank_account'] ?: null;
        }

        if (array_key_exists('next_of_kin_name', $validated)) {
            $memberUpdates['next_of_kin_name'] = $validated['next_of_kin_name'] ?: null;
        }
        if (array_key_exists('next_of_kin_relation', $validated)) {
            $memberUpdates['next_of_kin_relation'] = $validated['next_of_kin_relation'] ?: null;
        }
        if (array_key_exists('next_of_kin_phone', $validated)) {
            $memberUpdates['next_of_kin_phone'] = $validated['next_of_kin_phone'] ?: null;
        }
        if (array_key_exists('next_of_kin_address', $validated)) {
            $memberUpdates['next_of_kin_address'] = $validated['next_of_kin_address'] ?: null;
        }
        if (array_key_exists('spouse_name', $validated)) {
            $memberUpdates['spouse_name'] = $validated['spouse_name'] ?: null;
        }
        if (array_key_exists('spouse_phone', $validated)) {
            $memberUpdates['spouse_phone'] = $validated['spouse_phone'] ?: null;
        }
        if (array_key_exists('spouse_address', $validated)) {
            $memberUpdates['spouse_address'] = $validated['spouse_address'] ?: null;
        }

        if (array_key_exists('digital_signature', $validated)) {
            $memberUpdates['digital_signature'] = $validated['digital_signature'] ?: null;
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

        if ($memberUpdates !== []) {
            $this->checkAndMarkOnboardingComplete($member->fresh());
        }

        return redirect()
            ->route('member.profile', $request->boolean('edit') ? ['edit' => 1] : [])
            ->with('status', 'Profil anda berjaya dikemas kini.');
    }

    public function uploadPhoto(Request $request): JsonResponse
    {
        $member = $this->currentMember($request);

        $this->authorize('updateProfile', $member);

        $request->validate([
            'profile_photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $path = $this->memberPhotos->store($request->file('profile_photo'), $member);
        $url = $this->memberPhotos->url($path);

        return response()->json(['url' => $url]);
    }
}