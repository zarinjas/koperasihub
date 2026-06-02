<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectFinancingApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve_financing_applications');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}