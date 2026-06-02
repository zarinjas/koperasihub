<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Sila pilih fail CSV untuk dimuat naik.',
            'file.file' => 'Muat naik mesti berupa fail.',
            'file.mimes' => 'Format fail tidak disokong. Sila muat naik fail CSV sahaja.',
            'file.max' => 'Saiz fail melebihi had 5MB yang dibenarkan.',
        ];
    }
}