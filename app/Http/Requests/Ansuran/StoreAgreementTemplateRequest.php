<?php

namespace App\Http\Requests\Ansuran;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgreementTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama template diperlukan.',
            'content.required' => 'Kandungan template diperlukan.',
        ];
    }
}
