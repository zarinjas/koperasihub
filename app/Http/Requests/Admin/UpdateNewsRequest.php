<?php

namespace App\Http\Requests\Admin;

use App\Enums\NewsCategory;
use App\Enums\NewsStatus;
use App\Models\News;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_NEWS) ?? false;
    }

    public function rules(): array
    {
        $cooperativeId = $this->cooperativeId();
        $newsId = $this->route('news')?->id;

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::notIn(News::reservedSlugs()),
                Rule::unique('news', 'slug')
                    ->where(fn ($query) => $query->where('cooperative_id', $cooperativeId)->whereNull('deleted_at'))
                    ->ignore($newsId),
            ],
            'excerpt' => ['nullable', 'string', 'max:320'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'category' => ['nullable', Rule::in(NewsCategory::values())],
            'status' => ['required', Rule::in(NewsStatus::values())],
            'published_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slugSource = $this->input('slug') ?: $this->input('title');

        $this->merge([
            'slug' => filled($slugSource) ? Str::slug($slugSource) : null,
        ]);
    }

    public function attributes(): array
    {
        return [
            'title' => 'tajuk',
            'slug' => 'slug',
            'excerpt' => 'petikan',
            'content' => 'kandungan',
            'image' => 'imej',
            'category' => 'kategori',
            'status' => 'status',
            'published_at' => 'tarikh terbit',
        ];
    }

    private function cooperativeId(): int
    {
        $cooperativeId = $this->user()?->cooperative_id
            ?? app(SettingsService::class)->activeCooperative()?->id;

        if (! $cooperativeId) {
            throw ValidationException::withMessages([
                'cooperative' => 'Koperasi aktif tidak ditemui.',
            ]);
        }

        return $cooperativeId;
    }
}