<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageSectionType;
use App\Support\CmsSectionRegistry;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
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
            'data.image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $this->array('data');
            unset($data['image'], $data['image_url']);

            app(CmsSectionRegistry::class)->validate(
                $this->string('type')->toString(),
                $data,
                $this->array('settings'),
            );
        });
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Jenis seksyen diperlukan.',
            'type.in' => 'Jenis seksyen tidak dibenarkan.',
        ];
    }
}
