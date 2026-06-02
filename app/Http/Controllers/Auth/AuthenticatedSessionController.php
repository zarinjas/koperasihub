<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function createAdmin(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->intended($this->homeFor($request->user()));
        }

        return Inertia::render('Admin/Pages/Auth/Login', [
            'quickLoginEnabled' => $this->quickLoginEnabled(),
            'quickLoginOptions' => $this->adminQuickLoginOptions(),
        ]);
    }

    public function createMember(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->intended($this->homeFor($request->user()));
        }

        return Inertia::render('Member/Pages/Auth/Login', [
            'quickLoginEnabled' => $this->quickLoginEnabled(),
            'quickLoginOptions' => $this->memberQuickLoginOptions(),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended($this->homeFor($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.home');
    }

    public function quickLoginAdmin(Request $request): RedirectResponse
    {
        return $this->quickLogin($request, User::ROLE_ADMIN);
    }

    public function quickLoginSuperAdmin(Request $request): RedirectResponse
    {
        return $this->quickLogin($request, AccessControl::ROLE_SUPER_ADMIN);
    }

    public function quickLoginMember(Request $request): RedirectResponse
    {
        return $this->quickLogin($request, User::ROLE_MEMBER);
    }

    private function quickLogin(Request $request, string $role): RedirectResponse
    {
        abort_unless($this->quickLoginEnabled(), 404);

        $user = User::query()
            ->where('role', $role)
            ->first();

        if (! $user) {
            return back()->withErrors([
                'quickLogin' => 'Akaun demo belum tersedia. Sila jalankan seeder terlebih dahulu.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended($this->homeFor($user));
    }

    private function quickLoginEnabled(): bool
    {
        return app()->environment('local', 'testing', 'production');
    }

    private function memberQuickLoginOptions(): array
    {
        if (! $this->quickLoginEnabled()) {
            return [];
        }

        return [
            [
                'label' => 'Log Masuk Demo Ahli',
                'url' => route('member.quick-login'),
            ],
        ];
    }

    private function adminQuickLoginOptions(): array
    {
        if (! $this->quickLoginEnabled()) {
            return [];
        }

        return [
            [
                'label' => 'Log Masuk Demo Super Admin',
                'url' => route('admin.quick-login.super-admin'),
            ],
            [
                'label' => 'Log Masuk Demo Admin',
                'url' => route('admin.quick-login'),
            ],
        ];
    }

    private function homeFor(User $user): string
    {
        return $user->hasRole(AccessControl::ROLE_MEMBER) || $user->role === User::ROLE_MEMBER
            ? route('member.dashboard')
            : route('admin.dashboard');
    }
}