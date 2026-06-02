<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'identity_no' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line_1' => ['required', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'referred_by_member_id' => ['nullable', 'integer', 'exists:members,id'],
            'digital_signature' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama penuh diperlukan.',
            'identity_no.required' => 'Nombor kad pengenalan diperlukan.',
            'email.required' => 'Alamat e-mel diperlukan.',
            'email.email' => 'Sila masukkan alamat e-mel yang sah.',
            'phone.required' => 'Nombor telefon diperlukan.',
            'address_line_1.required' => 'Alamat diperlukan.',
            'date_of_birth.required' => 'Tarikh lahir diperlukan.',
            'date_of_birth.before' => 'Tarikh lahir mesti sebelum hari ini.',
            'gender.required' => 'Sila pilih jantina.',
            'gender.in' => 'Sila pilih jantina yang sah.',
        ];
    }
}