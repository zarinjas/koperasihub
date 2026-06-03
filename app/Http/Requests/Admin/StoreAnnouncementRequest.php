<?php

namespace App\Http\Requests\Admin;

use App\Enums\AnnouncementAudience;
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
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'audience' => ['required', Rule::in(AnnouncementAudience::values())],
            'is_pinned' => ['nullable', 'boolean'],
            'send_notification' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
            'specific_member_ids' => ['nullable', 'array'],
            'specific_member_ids.*' => ['integer', 'exists:members,id'],
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
            'image' => 'imej',
            'audience' => 'audiens',
            'is_pinned' => 'pin',
            'send_notification' => 'hantar notifikasi',
            'send_email' => 'hantar emel',
            'specific_member_ids' => 'ahli tertentu',
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