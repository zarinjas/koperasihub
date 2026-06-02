<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\MemberPasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Member/Pages/Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Sila masukkan alamat e-mel.',
            'email.email' => 'Sila masukkan alamat e-mel yang sah.',
        ]);

        $email = Str::lower(trim($validated['email']));

        $user = User::query()
            ->where('email', $email)
            ->where(function ($query): void {
                $query->where('user_type', 'member')
                    ->orWhere('role', 'member');
            })
            ->first();

        if ($user) {
            Password::sendResetLink(
                ['email' => $email],
                function ($user, $token): void {
                    $user->notify(new MemberPasswordReset($token, $user->email));
                },
            );
        }

        return back()->with('status', 'Jika e-mel ini berdaftar, pautan tetapan semula kata laluan akan dihantar.');
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Member/Pages/Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'token.required' => 'Token tetapan semula diperlukan.',
            'email.required' => 'Sila masukkan alamat e-mel.',
            'email.email' => 'Sila masukkan alamat e-mel yang sah.',
            'password.required' => 'Kata laluan diperlukan.',
            'password.min' => 'Kata laluan mestilah sekurang-kurangnya 8 aksara.',
            'password.confirmed' => 'Pengesahan kata laluan tidak sepadan.',
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function ($user, $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Pautan tetapan semula tidak sah atau telah tamat tempoh.',
            ]);
        }

        return redirect()->route('member.login')->with('status', 'Kata laluan telah berjaya ditetapkan semula. Sila log masuk menggunakan kata laluan baharu.');
    }
}