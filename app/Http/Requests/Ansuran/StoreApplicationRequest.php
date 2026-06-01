<?php

namespace App\Http\Requests\Ansuran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:ansuran_products,id'],
            'variant_id' => ['required', 'exists:ansuran_product_variants,id'],
            'tenure_option_id' => ['required', 'exists:ansuran_tenure_options,id'],
            'down_payment' => ['required', 'numeric', 'min:0'],
            'delivery_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'delivery_address' => ['required_if:delivery_method,delivery', 'nullable', 'string', 'max:500'],
            'guarantor_member_ids' => ['nullable', 'array'],
            'guarantor_member_ids.*' => ['exists:members,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk diperlukan.',
            'variant_id.required' => 'Varian produk diperlukan.',
            'tenure_option_id.required' => 'Tempoh ansuran diperlukan.',
            'down_payment.required' => 'Bayaran pendahuluan diperlukan.',
            'delivery_method.required' => 'Kaedah penghantaran diperlukan.',
            'delivery_address.required_if' => 'Alamat penghantaran diperlukan untuk kaedah penghantaran.',
        ];
    }
}
