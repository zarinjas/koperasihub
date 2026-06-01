<?php

namespace App\Http\Requests\Ansuran;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ansuran_category_id' => ['required', 'exists:ansuran_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'min_down_payment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'guarantor_count' => ['nullable', 'integer', 'min:0', 'max:2'],
            'status' => ['nullable', 'in:draf,aktif,tidak_aktif'],
        ];
    }

    public function messages(): array
    {
        return [
            'ansuran_category_id.required' => 'Kategori produk diperlukan.',
            'name.required' => 'Nama produk diperlukan.',
        ];
    }
}
