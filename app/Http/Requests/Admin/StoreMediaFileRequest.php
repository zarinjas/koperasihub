<?php

namespace App\Http\Requests\Admin;

use App\Enums\MediaVisibility;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_UPLOAD_MEDIA) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:5120'],
            'collection' => ['nullable', 'string', 'max:80'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'visibility' => ['required', Rule::in([
                MediaVisibility::Public->value,
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Sila pilih fail media.',
            'file.mimes' => 'Format fail tidak disokong.',
            'file.max' => 'Saiz fail melebihi had yang dibenarkan.',
            'visibility.required' => 'Tahap akses media diperlukan.',
        ];
    }
}
