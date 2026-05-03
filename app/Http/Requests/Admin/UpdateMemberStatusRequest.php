<?php

namespace App\Http\Requests\Admin;

use App\Enums\MemberStatus;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_SUSPEND_MEMBERS) ?? false;
    }

    public function rules(): array
    {
        return [
            'membership_status' => ['required', Rule::in(MemberStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'membership_status.required' => 'Status ahli diperlukan.',
        ];
    }
}
