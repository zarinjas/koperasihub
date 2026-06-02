<?php

namespace App\Http\Controllers\Member;

use App\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use App\Services\Settings\SettingsService;
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
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function create(Request $request): Response
    {
        $memberId = $request->session()->get('activation_member_id');
        $step = $memberId ? 2 : 1;
        $memberEmail = null;
        $memberPhone = null;

        if ($memberId) {
            $member = Member::find($memberId);
            $memberEmail = $member?->email;
            $memberPhone = $member?->phone;
        }

        $contactSettings = $this->settings->group('contact');

        return Inertia::render('Member/Pages/Auth/Activate', [
            'step' => $step,
            'memberEmail' => $memberEmail,
            'memberPhone' => $memberPhone,
            'contactEmail' => $contactSettings['email'] ?? null,
        ]);
    }

    public function verifyStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identity_no' => ['required', 'string'],
        ], [
            'identity_no.required' => 'No. kad pengenalan diperlukan.',
        ]);

        $member = Member::query()
            ->where('identity_no', $validated['identity_no'])
            ->first();

        if (! $member) {
            throw ValidationException::withMessages([
                'identity_no' => 'No. kad pengenalan tidak dijumpai dalam sistem.',
            ]);
        }

        if ($member->membership_status !== MemberStatus::Active) {
            throw ValidationException::withMessages([
                'identity_no' => 'Akaun ahli tidak aktif. Sila hubungi pihak koperasi.',
            ]);
        }

        if ($member->user_id) {
            $user = User::find($member->user_id);
            if ($user && $user->status === 'active') {
                throw ValidationException::withMessages([
                    'identity_no' => 'Akaun portal telah diaktifkan. Sila log masuk menggunakan No. Ahli / No. IC / E-mel anda.',
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
                'identity_no' => 'Sesi pengaktifan telah tamat. Sila cuba lagi.',
            ]);
        }

        $member = Member::query()
            ->where('id', $memberId)
            ->where('membership_status', MemberStatus::Active->value)
            ->first();

        if (! $member) {
            $request->session()->forget('activation_member_id');

            return redirect()->route('member.activate')->withErrors([
                'identity_no' => 'Rekod ahli tidak sah atau tidak aktif.',
            ]);
        }

        if ($member->user_id) {
            $request->session()->forget('activation_member_id');

            return redirect()->route('member.login')->with('status', 'Akaun portal telah diaktifkan. Sila log masuk.');
        }

        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        $messages = [
            'password.required' => 'Kata laluan diperlukan.',
            'password.min' => 'Kata laluan mestilah sekurang-kurangnya 8 aksara.',
            'password.confirmed' => 'Pengesahan kata laluan tidak sepadan.',
        ];

        if (! $member->email) {
            $rules['email'] = ['required', 'email', 'max:255'];
            $messages['email.required'] = 'Alamat e-mel diperlukan.';
            $messages['email.email'] = 'Sila masukkan alamat e-mel yang sah.';
        }

        $validated = $request->validate($rules, $messages);

        if ($request->has('email')) {
            $email = Str::lower(trim($request->input('email')));
        } elseif ($member->email) {
            $email = Str::lower(trim($member->email));
        } else {
            $email = null;
        }

        if ($email) {
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
        }

        $user = DB::transaction(function () use ($member, $email, $validated): User {
            $user = User::query()->create([
                'cooperative_id' => $member->cooperative_id,
                'name' => $member->full_name,
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_MEMBER,
                'user_type' => 'member',
                'status' => 'active',
                'phone' => $member->phone,
            ]);

            $user->assignRole(User::ROLE_MEMBER);

            $member->update([
                'user_id' => $user->id,
                'email' => $member->email ?: $email,
                'phone' => $member->phone,
                'portal_activated_at' => now(),
            ]);

            return $user;
        });

        $request->session()->forget('activation_member_id');

        auth()->login($user);

        return redirect()->route('member.dashboard')->with('status', 'Akaun portal anda berjaya diaktifkan. Sila lengkapkan profil anda.');
    }
}