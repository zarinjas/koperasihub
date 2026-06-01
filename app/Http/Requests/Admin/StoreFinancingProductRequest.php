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
            'rate_note' => ['nullable', 'string'],
            'rate_image' => ['nullable', 'image', 'max:10240'],
            'form_template' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'requires_guarantor' => ['nullable'],
            'guarantor_count' => ['nullable', 'integer', 'min:1', 'max:5'],
            'requires_stamped_upload' => ['nullable'],
            'stamped_upload_instructions' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}