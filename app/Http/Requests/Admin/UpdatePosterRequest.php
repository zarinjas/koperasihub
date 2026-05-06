<?php

namespace App\Http\Requests\Admin;

use App\Enums\PosterStatus;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdatePosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_POSTERS) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
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

    public function ensureRatio(int $width, int $height): void
    {
        $ratio = $width / $height;
        $target = 1080 / 1350;
        $tolerance = 0.02;

        if (abs($ratio - $target) > $tolerance) {
            throw ValidationException::withMessages([
                'image' => 'Nisbah imej mestilah 4:5 (1080×1350px). Nisbah semasa: '.round($ratio, 2).'.',
            ]);
        }
    }
}
