<?php

namespace App\Http\Requests\Admin;

use App\Enums\FinancingFieldType;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancingProductFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_financing_products');
    }

    public function rules(): array
    {
        return [
            'financing_product_section_id' => ['nullable', 'exists:financing_product_sections,id'],
            'label' => [
                in_array($this->input('type'), ['note', 'instruction_text', 'document_checklist', 'signature_block']) ? 'nullable' : 'required',
                'string', 'max:255',
            ],
            'field_key' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', FinancingFieldType::values())],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string'],
            'is_required' => ['boolean'],
            'options' => ['nullable', 'string'],
            'settings_json' => ['nullable', 'array'],
            'settings_json.content' => ['nullable', 'string'],
            'settings_json.checklist_items' => ['nullable', 'array'],
            'settings_json.checklist_items.*' => ['string', 'max:500'],
            'settings_json.checklist_notes' => ['nullable', 'array'],
            'settings_json.checklist_notes.*' => ['string', 'max:500'],
            'settings_json.left_label' => ['nullable', 'string', 'max:255'],
            'settings_json.right_label' => ['nullable', 'string', 'max:255'],
            'settings_json.enable_left' => ['nullable', 'boolean'],
            'settings_json.enable_right' => ['nullable', 'boolean'],
            'settings_json.columns' => ['nullable', 'array'],
            'settings_json.columns.*.key' => ['required_with:settings_json.columns', 'string', 'max:80'],
            'settings_json.columns.*.label' => ['required_with:settings_json.columns', 'string', 'max:255'],
            'settings_json.columns.*.type' => ['nullable', 'string', 'in:text,number,currency,date'],
            'settings_json.columns.*.required' => ['nullable', 'boolean'],
            'settings_json.min_rows' => ['nullable', 'integer', 'min:0', 'max:100'],
            'settings_json.max_rows' => ['nullable', 'integer', 'min:1', 'max:100'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }
}