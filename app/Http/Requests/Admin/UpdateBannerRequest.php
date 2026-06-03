<?php

namespace App\Http\Requests\Admin;

use App\Enums\BannerStatus;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_BANNERS) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(BannerStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tajuk banner diperlukan.',
            'image.image' => 'Fail mestilah imej.',
            'image.mimes' => 'Imej mestilah dalam format JPEG, PNG atau WebP.',
            'image.max' => 'Saiz imej maksimum 5MB.',
            'link_url.required' => 'URL pautan diperlukan.',
            'status.required' => 'Status diperlukan.',
        ];
    }
}