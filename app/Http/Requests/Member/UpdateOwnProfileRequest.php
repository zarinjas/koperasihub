<?php

namespace App\Http\Requests\Member;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
