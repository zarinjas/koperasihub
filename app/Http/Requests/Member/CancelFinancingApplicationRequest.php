<?php

namespace App\Http\Requests\Member;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class CancelFinancingApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
