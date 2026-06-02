<?php

namespace App\Http\Requests\Member;

use App\Models\FinancingProduct;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancingApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = FinancingProduct::find($this->input('financing_product_id'));

        return [
            'financing_category_id' => ['required', 'exists:financing_categories,id'],
            'financing_product_id' => ['required', 'exists:financing_products,id'],
            'amount_requested' => [
                'required', 'numeric', 'min:1',
                $product?->min_amount ? 'min:'.$product->min_amount : '',
                $product?->max_amount ? 'max:'.$product->max_amount : '',
            ],
            'tenure_months' => [
                'required', 'integer', 'min:1',
                $product?->min_tenure_months ? 'min:'.$product->min_tenure_months : '',
                $product?->max_tenure_months ? 'max:'.$product->max_tenure_months : '',
            ],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'monthly_commitment' => ['nullable', 'numeric', 'min:0'],
            'employment_notes' => ['nullable', 'string', 'max:1000'],
            'answers' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'guarantor_member_ids' => ['nullable', 'array'],
            'guarantor_member_ids.*' => ['exists:members,id'],
            'stamped_form' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}