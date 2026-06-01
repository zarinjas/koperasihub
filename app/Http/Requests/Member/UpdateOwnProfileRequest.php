<?php

namespace App\Http\Requests\Member;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        $member = $this->user()?->member;

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('members', 'email')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id))
                    ->ignore($member?->id),
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama penuh diperlukan.',
            'email.required' => 'E-mel diperlukan.',
            'email.email' => 'Sila masukkan alamat e-mel yang sah.',
            'email.unique' => 'E-mel ini telah digunakan oleh rekod lain.',
            'date_of_birth.date' => 'Tarikh lahir tidak sah.',
            'gender.in' => 'Sila pilih jantina yang sah.',
            'profile_photo.mimes' => 'Format fail tidak disokong.',
            'profile_photo.max' => 'Saiz fail melebihi had yang dibenarkan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->has('full_name')) {
            $payload['full_name'] = filled($this->full_name) ? trim((string) $this->full_name) : null;
        }

        if ($this->has('email')) {
            $payload['email'] = filled($this->email) ? mb_strtolower(trim((string) $this->email)) : null;
        }

        if ($this->has('phone')) {
            $payload['phone'] = filled($this->phone) ? trim((string) $this->phone) : null;
        }

        if ($this->has('address')) {
            $payload['address'] = filled($this->address) ? trim((string) $this->address) : null;
        }

        if ($this->has('occupation')) {
            $payload['occupation'] = filled($this->occupation) ? trim((string) $this->occupation) : null;
        }

        if ($this->has('employer_name')) {
            $payload['employer_name'] = filled($this->employer_name) ? trim((string) $this->employer_name) : null;
        }

        if ($this->has('gender')) {
            $payload['gender'] = filled($this->gender) ? trim((string) $this->gender) : null;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}