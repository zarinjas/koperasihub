<?php

namespace App\Http\Requests\Admin;

use App\Models\FinancingCategory;
use App\Models\Unit;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancingProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MANAGE_FINANCING_PRODUCTS) ?? false;
    }

    public function rules(): array
    {
        return [
            'financing_category_id' => [
                'required',
                'integer',
                Rule::exists(FinancingCategory::class, 'id')->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
            'unit_id' => [
                'nullable',
                'integer',
                Rule::exists(Unit::class, 'id')->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'min_tenure_months' => ['nullable', 'integer', 'min:1'],
            'max_tenure_months' => ['nullable', 'integer', 'min:1'],
            'requires_guarantor' => ['nullable', 'boolean'],
            'guarantor_count' => ['nullable', 'integer', 'min:0'],
            'required_documents_text' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
