<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancingProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_financing_products');
    }

    public function rules(): array
    {
        return [
            'financing_category_id' => ['required', 'exists:financing_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0', 'gte:min_amount'],
            'min_tenure_months' => ['nullable', 'integer', 'min:1'],
            'max_tenure_months' => ['nullable', 'integer', 'min:1', 'gte:min_tenure_months'],
            'annual_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rate_tiers_json' => ['nullable', 'array'],
            'rate_tiers_json.*.min_months' => ['required_with:rate_tiers_json', 'integer', 'min:1'],
            'rate_tiers_json.*.max_months' => ['required_with:rate_tiers_json', 'integer', 'min:1', 'gte:rate_tiers_json.*.min_months'],
            'rate_tiers_json.*.rate_percent' => ['required_with:rate_tiers_json', 'numeric', 'min:0', 'max:100'],
            'rate_note' => ['nullable', 'string'],
            'rate_image' => ['nullable', 'image', 'max:10240'],
            'form_template' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'requires_guarantor' => ['nullable'],
            'guarantor_count' => ['nullable', 'integer', 'min:1', 'max:5'],
            'requires_stamped_upload' => ['nullable'],
            'stamped_upload_instructions' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}