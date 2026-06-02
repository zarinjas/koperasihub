<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCarumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_caruman') ?? false;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['sometimes', 'required', 'integer', 'exists:members,id'],
            'year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2099'],
            'caruman_semasa' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'caruman_keseluruhan' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'dividen' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'caruman_semasa.required' => 'Jumlah Caruman Setakat Ini diperlukan.',
            'caruman_semasa.numeric' => 'Caruman Setakat Ini mestilah nombor.',
            'caruman_semasa.min' => 'Caruman Setakat Ini tidak boleh negatif.',
            'caruman_keseluruhan.required' => 'Jumlah Caruman Keseluruhan diperlukan.',
            'caruman_keseluruhan.numeric' => 'Caruman Keseluruhan mestilah nombor.',
            'caruman_keseluruhan.min' => 'Caruman Keseluruhan tidak boleh negatif.',
            'dividen.required' => 'Jumlah Dividen diperlukan.',
            'dividen.numeric' => 'Dividen mestilah nombor.',
            'dividen.min' => 'Dividen tidak boleh negatif.',
            'notes.max' => 'Nota tidak boleh melebihi 500 aksara.',
        ];
    }
}