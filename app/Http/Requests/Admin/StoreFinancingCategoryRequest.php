<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancingCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_financing_categories');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:guaranteed,non_guaranteed'],
            'icon' => ['nullable', 'string', 'max:100'],
        ];
    }
}