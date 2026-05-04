<?php

namespace App\Http\Requests\Admin;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS) ?? false;
    }

    public function rules(): array
    {
        return [
            'logo' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Sila pilih fail logo untuk dimuat naik.',
            'logo.file' => 'Logo mesti berupa fail.',
            'logo.image' => 'Logo mesti berupa imej.',
            'logo.mimes' => 'Format logo yang disokong: PNG, JPG, JPEG, WEBP, SVG.',
            'logo.max' => 'Saiz fail logo tidak boleh melebihi 2MB.',
        ];
    }
}
