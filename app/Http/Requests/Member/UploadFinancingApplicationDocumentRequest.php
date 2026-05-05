<?php

namespace App\Http\Requests\Member;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class UploadFinancingApplicationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
