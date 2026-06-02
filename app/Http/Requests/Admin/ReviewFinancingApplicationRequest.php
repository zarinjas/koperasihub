<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewFinancingApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review_financing_applications');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}