<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Models\Page;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CREATE_PAGES) ?? false;
    }

    public function rules(): array
    {
        $cooperativeId = $this->cooperativeId();

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::notIn(Page::reservedSlugs()),
                Rule::unique('pages', 'slug')->where(fn ($query) => $query->where('cooperative_id', $cooperativeId)),
            ],
            'template' => ['required', Rule::in(PageTemplate::values())],
            'summary' => ['nullable', 'string'],
            'status' => ['required', Rule::in(PageStatus::values())],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tajuk halaman diperlukan.',
            'slug.unique' => 'Slug ini telah digunakan untuk koperasi semasa.',
            'slug.not_in' => 'Slug ini disimpan untuk laluan sistem.',
            'template.required' => 'Templat halaman diperlukan.',
            'status.required' => 'Status halaman diperlukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $slugSource = $this->input('slug') ?: $this->input('title');

        $this->merge([
            'slug' => filled($slugSource) ? Str::slug($slugSource) : null,
        ]);
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