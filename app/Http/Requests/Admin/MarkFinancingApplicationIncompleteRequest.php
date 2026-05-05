<?php

namespace App\Http\Requests\Admin;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class MarkFinancingApplicationIncompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_REVIEW_FINANCING_APPLICATIONS) ?? false;
    }

    public function rules(): array
    {
        return [
            'decision_notes' => ['required', 'string', 'max:2000'],
        ];
    }
}
