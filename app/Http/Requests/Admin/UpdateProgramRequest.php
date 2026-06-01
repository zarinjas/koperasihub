<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProgramStatus;
use App\Enums\ProgramType;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_EDIT_PROGRAMS) ?? false;
    }

    public function rules(): array
    {
        $program = $this->route('program');
        $cooperativeId = $this->cooperativeId();

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:280',
                Rule::unique('programs', 'slug')
                    ->ignore($program)
                    ->where(fn ($query) => $query->where('cooperative_id', $cooperativeId)),
            ],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'program_type' => ['required', Rule::in(ProgramType::values())],
            'location' => ['nullable', 'string', 'max:500'],
            'online_url' => ['nullable', 'url', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:start_date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'status' => ['required', Rule::in(ProgramStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    private function cooperativeId(): ?int
    {
        return app(SettingsService::class)->activeCooperative()?->id;
    }
}