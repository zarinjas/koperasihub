<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageSectionType;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_PAGES) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(PageSectionType::values())],
            'name' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Jenis seksyen diperlukan.',
            'type.in' => 'Jenis seksyen tidak dibenarkan.',
        ];
    }
}
