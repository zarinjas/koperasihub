<?php

namespace App\Http\Requests\Admin;

use App\Models\FormCategory;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CREATE_FORMS) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique(FormCategory::class, 'slug')
                    ->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where(fn ($query) => $query->where('cooperative_id', $this->user()?->cooperative_id))],
        ];
    }
}