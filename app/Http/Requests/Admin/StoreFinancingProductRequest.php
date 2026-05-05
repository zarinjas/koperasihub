<?php

namespace App\Http\Requests\Admin;

use App\Models\FinancingCategory;
use App\Models\Unit;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

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
            // slug is auto-generated; not accepted from user input
            'description' => ['nullable', 'string', 'max:2000'],
            'eligibility_terms' => ['nullable', 'string', 'max:10000'],
            'product_terms' => ['nullable', 'string', 'max:10000'],
            'application_notes' => ['nullable', 'string', 'max:10000'],
            'application_instructions' => ['nullable', 'string', 'max:10000'],
            'required_documents_note' => ['nullable', 'string', 'max:10000'],
            'officer_contact_name' => ['nullable', 'string', 'max:255'],
            'officer_contact_phone' => ['nullable', 'string', 'max:255'],
            'officer_contact_email' => ['nullable', 'email', 'max:255'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'min_tenure_months' => ['nullable', 'integer', 'min:1'],
            'max_tenure_months' => ['nullable', 'integer', 'min:1'],
            'rate_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_rate_image' => ['nullable', 'boolean'],
            'annual_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rate_note' => ['nullable', 'string', 'max:1000'],
            'requires_guarantor' => ['nullable', 'boolean'],
            'guarantor_count' => ['nullable', 'integer', 'min:0'],
            'required_documents_text' => ['nullable', 'string', 'max:5000'],
            'consent_pdf' => ['nullable', File::types(['pdf'])->max(10 * 1024)],
            'undertaking_pdf' => ['nullable', File::types(['pdf'])->max(10 * 1024)],
            'guide_pdf' => ['nullable', File::types(['pdf'])->max(10 * 1024)],
            'official_form_template_pdf' => ['nullable', File::types(['pdf'])->max(10 * 1024)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
