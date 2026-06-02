<?php

namespace App\Http\Requests\Admin;

use App\Enums\PosterStatus;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CREATE_POSTERS) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(PosterStatus::values())],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tajuk',
            'image' => 'imej',
            'alt_text' => 'teks alternatif',
            'status' => 'status',
        ];
    }

}