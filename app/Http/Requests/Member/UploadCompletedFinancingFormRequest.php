<?php

namespace App\Http\Requests\Member;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadCompletedFinancingFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        return [
            'completed_form' => ['required', File::types(['pdf'])->max(10 * 1024)],
        ];
    }

    public function messages(): array
    {
        return [
            'completed_form.required' => 'Sila muat naik borang lengkap bercop.',
            'completed_form.file' => 'Fail yang dimuat naik tidak sah.',
            'completed_form.mimes' => 'Format fail tidak disokong. Sila gunakan PDF.',
            'completed_form.max' => 'Saiz fail melebihi had 10MB.',
        ];
    }
}