<?php

namespace App\Http\Requests\Admin;

use App\Enums\FormSubmissionStatus;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_VIEW_FORM_SUBMISSIONS) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(FormSubmissionStatus::values())],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
