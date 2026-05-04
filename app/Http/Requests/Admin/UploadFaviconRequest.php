<?php

namespace App\Http\Requests\Admin;

use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;

class UploadFaviconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_SETTINGS) ?? false;
    }

    public function rules(): array
    {
        return [
            'favicon' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'favicon.required' => 'Sila pilih fail favicon untuk dimuat naik.',
            'favicon.file' => 'Favicon mesti berupa fail.',
            'favicon.image' => 'Favicon mesti berupa imej.',
            'favicon.mimes' => 'Format favicon yang disokong: PNG, JPG, JPEG, WEBP, SVG.',
            'favicon.max' => 'Saiz fail favicon tidak boleh melebihi 512KB.',
        ];
    }
}
