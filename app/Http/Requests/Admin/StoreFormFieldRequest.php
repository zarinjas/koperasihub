<?php

namespace App\Http\Requests\Admin;

use App\Enums\FormFieldType;
use App\Models\FormField;
use App\Models\FormSection;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_FORMS) ?? false;
    }

    public function rules(): array
    {
        $form = $this->route('onlineForm');

        return [
            'form_section_id' => [
                'required',
                'integer',
                Rule::exists(FormSection::class, 'id')
                    ->where(fn ($query) => $query->where('online_form_id', $form?->id)),
            ],
            'label' => ['required', 'string', 'max:255'],
            'field_key' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(FormField::class, 'field_key')->where(fn ($query) => $query->where('online_form_id', $form?->id)),
            ],
            'type' => ['required', Rule::in(FormFieldType::values())],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:3000'],
            'is_required' => ['required', 'boolean'],
            'options_text' => ['nullable', 'string', 'max:5000'],
            'validation_json' => ['nullable', 'array'],
            'settings_json' => ['nullable', 'array'],
            'settings_json.display_mode' => ['nullable', 'string', 'in:online_and_print,online_only,print_only'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}