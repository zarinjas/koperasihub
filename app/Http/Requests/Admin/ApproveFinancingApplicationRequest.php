<?php

namespace App\Http\Requests\Admin;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class ApproveFinancingApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_APPROVE_FINANCING_APPLICATIONS) ?? false;
    }

    public function rules(): array
    {
        return [
            'approved_amount' => ['required', 'numeric', 'min:0'],
            'approved_tenure_months' => ['required', 'integer', 'min:1'],
            'decision_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
