<?php

namespace App\Http\Requests\Admin;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class StoreFormSectionFromTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_FORMS) ?? false;
    }

    public function rules(): array
    {
        return [
            'template_ref' => ['required', 'string', 'max:255'],
        ];
    }
}