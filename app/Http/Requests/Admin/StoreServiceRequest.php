<?php

namespace App\Http\Requests\Admin;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Services\Settings\SettingsService;
use App\Support\AccessControl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessControl::PERMISSION_CREATE_SERVICES) ?? false;
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
                Rule::notIn(Service::reservedSlugs()),
                Rule::unique('services', 'slug')
                    ->where(fn ($query) => $query->where('cooperative_id', $cooperativeId)),
            ],
            'category' => ['nullable', 'string', 'max:80'],
            'summary' => ['nullable', 'string', 'max:320'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'icon' => ['nullable', 'string', 'max:80'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(ServiceStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
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
            'category' => 'kategori',
            'summary' => 'ringkasan',
            'description' => 'penerangan',
            'image' => 'imej',
            'icon' => 'ikon',
            'contact_name' => 'nama pegawai',
            'contact_phone' => 'telefon pegawai',
            'contact_email' => 'e-mel pegawai',
            'whatsapp' => 'WhatsApp',
            'button_text' => 'label CTA',
            'button_url' => 'pautan CTA',
            'status' => 'status',
            'is_featured' => 'sorotan',
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