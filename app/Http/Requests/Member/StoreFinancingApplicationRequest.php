<?php

namespace App\Http\Requests\Member;

use App\Models\FinancingProduct;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancingApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_MEMBER_ACCESS) ?? false;
    }

    public function rules(): array
    {
        return [
            'financing_product_id' => [
                'required',
                'integer',
                Rule::exists(FinancingProduct::class, 'id')->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
            'amount_requested' => ['required', 'numeric', 'min:0.01'],
            'tenure_months' => ['required', 'integer', 'min:1'],
            'purpose' => ['required', 'string', 'max:3000'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'employment_notes' => ['nullable', 'string', 'max:2000'],
            'guarantor_member_ids' => ['array'],
            'guarantor_member_ids.*' => ['integer'],
            'documents' => ['array'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'custom_answers' => ['nullable', 'array'],
            'custom_answers.*' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
