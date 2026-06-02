<?php

namespace App\Http\Requests\Auth;

use App\Models\Member;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'login' => ['required_without:email', 'string'],
            'email' => ['required_without:login', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'login.required_without' => 'Sila masukkan No. Ahli, No. IC atau e-mel.',
            'email.required_without' => 'Sila masukkan alamat e-mel.',
            'email.email' => 'Alamat e-mel tidak sah.',
            'password.required' => 'Sila masukkan kata laluan.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->input('login') ?? $this->input('email') ?? '');
        $password = $this->input('password');

        $credentials = $this->resolveCredentials($login, $password);

        if (! $credentials || ! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => 'Maklumat log masuk tidak sepadan dengan rekod kami.',
                'email' => 'Maklumat log masuk tidak sepadan dengan rekod kami.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function resolveCredentials(string $login, string $password): ?array
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $login, 'password' => $password];
        }

        $member = Member::query()
            ->where('identity_no', $login)
            ->orWhere('member_no', $login)
            ->first();

        if (! $member || ! $member->user_id) {
            return null;
        }

        $user = $member->user;

        if (! $user || $user->status !== 'active') {
            return null;
        }

        return ['email' => $user->email, 'password' => $password];
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        $value = $this->string('login')->toString() ?: $this->string('email')->toString();

        return Str::transliterate(Str::lower($value).'|'.$this->ip());
    }
}