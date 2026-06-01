<?php

namespace App\Http\Controllers\Member;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ActivationController extends Controller
{
    public function create(Request $request): Response
    {
        $memberId = $request->session()->get('activation_member_id');
        $step = $memberId ? 2 : 1;

        return Inertia::render('Member/Pages/Auth/Activate', [
            'step' => $step,
        ]);
    }

    public function verifyStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_no' => ['required', 'string'],
            'identity_no' => ['required', 'string'],
            'date_of_birth' => ['required', 'date'],
        ], [
            'member_no.required' => 'No. ahli diperlukan.',
            'identity_no.required' => 'No. kad pengenalan diperlukan.',
            'date_of_birth.required' => 'Tarikh lahir diperlukan.',
            'date_of_birth.date' => 'Format tarikh lahir tidak sah.',
        ]);

        $member = Member::query()
            ->where('member_no', $validated['member_no'])
            ->where('identity_no', $validated['identity_no'])
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'member_no' => 'Maklumat yang dimasukkan tidak sepadan dengan rekod ahli.',
            ]);
        }

        if ($member->date_of_birth && $member->date_of_birth->format('Y-m-d') !== $validated['date_of_birth']) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Maklumat yang dimasukkan tidak sepadan dengan rekod ahli.',
            ]);
        }

        if ($member->membership_status !== MemberStatus::Active) {
            throw ValidationException::withMessages([
                'member_no' => 'Akaun ahli tidak aktif. Sila hubungi pihak koperasi.',
            ]);
        }

        if ($member->user_id) {
            $user = User::find($member->user_id);
            if ($user && $user->status === 'active') {
                throw ValidationException::withMessages([
                    'member_no' => 'Akaun portal telah diaktifkan. Sila log masuk menggunakan email dan kata laluan anda.',
                ]);
            }
        }

        $request->session()->put('activation_member_id', $member->id);

        return redirect()->route('member.activate');
    }

    public function complete(Request $request): RedirectResponse
    {
        $memberId = $request->session()->get('activation_member_id');

        if (! $memberId) {
            return redirect()->route('member.activate')->withErrors([
                'member_no' => 'Sesi pengaktifan telah tamat. Sila cuba lagi.',
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Alamat e-mel diperlukan.',
            'email.email' => 'Sila masukkan alamat e-mel yang sah.',
            'password.required' => 'Kata laluan diperlukan.',
            'password.min' => 'Kata laluan mestilah sekurang-kurangnya 8 aksara.',
            'password.confirmed' => 'Pengesahan kata laluan tidak sepadan.',
        ]);

        $member = Member::query()
            ->where('id', $memberId)
            ->where('membership_status', MemberStatus::Active->value)
            ->first();

        if (! $member) {
            $request->session()->forget('activation_member_id');

            return redirect()->route('member.activate')->withErrors([
                'member_no' => 'Rekod ahli tidak sah atau tidak aktif.',
            ]);
        }

        if ($member->user_id) {
            $request->session()->forget('activation_member_id');

            return redirect()->route('member.login')->with('status', 'Akaun portal telah diaktifkan. Sila log masuk.');
        }

        $email = Str::lower(trim($validated['email']));

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser) {
            if ($existingUser->member && $existingUser->member->id === $member->id) {
                $request->session()->forget('activation_member_id');
                return redirect()->route('member.login')->with('status', 'Akaun portal telah diaktifkan. Sila log masuk.');
            }

            throw ValidationException::withMessages([
                'email' => 'Alamat e-mel ini sudah digunakan oleh pengguna lain.',
            ]);
        }

        DB::transaction(function () use ($member, $email, $validated): void {
            $user = User::query()->create([
                'cooperative_id' => $member->cooperative_id,
                'name' => $member->full_name,
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_MEMBER,
                'user_type' => 'member',
                'status' => 'active',
                'phone' => $validated['phone'] ?: $member->phone,
            ]);

            $user->assignRole(User::ROLE_MEMBER);

            $member->update([
                'user_id' => $user->id,
                'email' => $member->email ?: $email,
                'phone' => $member->phone ?: $validated['phone'],
                'portal_activated_at' => now(),
            ]);
        });

        $request->session()->forget('activation_member_id');

        return redirect()->route('member.login')->with('status', 'Akaun portal anda telah diaktifkan. Sila log masuk menggunakan email dan kata laluan yang ditetapkan.');
    }
}