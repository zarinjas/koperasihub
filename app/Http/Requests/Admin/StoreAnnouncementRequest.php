<?php

namespace App\Http\Requests\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CREATE_ANNOUNCEMENTS) ?? false;
    }

    public function rules(): array
    {
        $cooperativeId = $this->cooperativeId();

        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::notIn(Announcement::reservedSlugs()),
                Rule::unique('announcements', 'slug')
                    ->where(fn ($query) => $query->where('cooperative_id', $cooperativeId)),
            ],
            'summary' => ['nullable', 'string', 'max:320'],
            'content' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'audience' => ['required', Rule::in(AnnouncementAudience::values())],
            'status' => ['required', Rule::in(AnnouncementStatus::values())],
            'is_pinned' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
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
            'summary' => 'ringkasan',
            'content' => 'kandungan',
            'image_path' => 'imej',
            'audience' => 'audiens',
            'status' => 'status',
            'is_pinned' => 'pin',
            'published_at' => 'tarikh terbit',
            'expires_at' => 'tarikh tamat',
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
