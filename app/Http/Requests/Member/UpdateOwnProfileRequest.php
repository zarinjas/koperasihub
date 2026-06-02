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
            'address_line_1' => ['nullable', 'string', 'max:500'],
            'address_line_2' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'employer' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'bank' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:50'],
'next_of_kin_name' => ['nullable', 'string', 'max:255'],
'next_of_kin_relation' => ['nullable', 'string', 'max:50'],
'next_of_kin_phone' => ['nullable', 'string', 'max:50'],
            'next_of_kin_address' => ['nullable', 'string', 'max:1000'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'spouse_phone' => ['nullable', 'string', 'max:50'],
            'spouse_address' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'digital_signature' => ['nullable', 'string'],
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
            'profile_photo.file' => 'Fail gambar tidak sah. Sila pilih fail gambar yang betul.',
            'profile_photo.mimes' => 'Hanya format JPG, JPEG, PNG dan WEBP dibenarkan.',
            'profile_photo.max' => 'Saiz gambar mestilah kurang daripada 2MB.',
            'profile_photo.uploaded' => 'Gambar gagal dimuat naik. Sila cuba dengan saiz yang lebih kecil.',
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

        if ($this->has('address_line_1')) {
            $payload['address_line_1'] = filled($this->address_line_1) ? trim((string) $this->address_line_1) : null;
        }

        if ($this->has('address_line_2')) {
            $payload['address_line_2'] = filled($this->address_line_2) ? trim((string) $this->address_line_2) : null;
        }

        if ($this->has('city')) {
            $payload['city'] = filled($this->city) ? trim((string) $this->city) : null;
        }

        if ($this->has('state')) {
            $payload['state'] = filled($this->state) ? trim((string) $this->state) : null;
        }

        if ($this->has('postcode')) {
            $payload['postcode'] = filled($this->postcode) ? trim((string) $this->postcode) : null;
        }

        if ($this->has('position')) {
            $payload['position'] = filled($this->position) ? trim((string) $this->position) : null;
        }

        if ($this->has('department')) {
            $payload['department'] = filled($this->department) ? trim((string) $this->department) : null;
        }

        if ($this->has('employer')) {
            $payload['employer'] = filled($this->employer) ? trim((string) $this->employer) : null;
        }

        if ($this->has('salary')) {
            $payload['salary'] = filled($this->salary) ? (float) $this->salary : null;
        }

        if ($this->has('bank')) {
            $payload['bank'] = filled($this->bank) ? trim((string) $this->bank) : null;
        }

        if ($this->has('bank_account')) {
            $payload['bank_account'] = filled($this->bank_account) ? trim((string) $this->bank_account) : null;
        }

if ($this->has('next_of_kin_name')) {
    $payload['next_of_kin_name'] = filled($this->next_of_kin_name) ? trim((string) $this->next_of_kin_name) : null;
}
if ($this->has('next_of_kin_relation')) {
    $payload['next_of_kin_relation'] = filled($this->next_of_kin_relation) ? trim((string) $this->next_of_kin_relation) : null;
}
if ($this->has('next_of_kin_phone')) {
            $payload['next_of_kin_phone'] = filled($this->next_of_kin_phone) ? trim((string) $this->next_of_kin_phone) : null;
        }
        if ($this->has('next_of_kin_address')) {
            $payload['next_of_kin_address'] = filled($this->next_of_kin_address) ? trim((string) $this->next_of_kin_address) : null;
        }
        if ($this->has('spouse_name')) {
            $payload['spouse_name'] = filled($this->spouse_name) ? trim((string) $this->spouse_name) : null;
        }
        if ($this->has('spouse_phone')) {
            $payload['spouse_phone'] = filled($this->spouse_phone) ? trim((string) $this->spouse_phone) : null;
        }
        if ($this->has('spouse_address')) {
            $payload['spouse_address'] = filled($this->spouse_address) ? trim((string) $this->spouse_address) : null;
        }

        if ($this->has('gender')) {
            $payload['gender'] = filled($this->gender) ? trim((string) $this->gender) : null;
        }

        if ($this->has('marital_status')) {
            $payload['marital_status'] = filled($this->marital_status) ? trim((string) $this->marital_status) : null;
        }

        if ($this->has('digital_signature')) {
            $payload['digital_signature'] = filled($this->digital_signature) ? trim((string) $this->digital_signature) : null;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}