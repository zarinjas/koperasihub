<?php

namespace App\Services\Settings;

use App\Models\Cooperative;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    public const GROUPS = ['brand', 'contact', 'social', 'seo', 'system'];

    public function activeCooperative(): ?Cooperative
    {
        if (! Schema::hasTable('cooperatives')) {
            return null;
        }

        $cooperativeId = Cache::rememberForever('settings.active_cooperative_id', function (): ?int {
            return Cooperative::query()
                ->where('status', 'active')
                ->orderBy('id')
                ->value('id');
        });

        return $cooperativeId ? Cooperative::query()->find($cooperativeId) : null;
    }

    public function grouped(?int $cooperativeId = null, bool $publicOnly = false): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        $cooperativeId ??= $this->activeCooperative()?->id;
        $scope = $publicOnly ? 'public' : 'all';

        return Cache::rememberForever("settings.grouped.{$scope}.{$cooperativeId}", function () use ($cooperativeId, $publicOnly): array {
            $query = Setting::query()
                ->when($cooperativeId, fn ($query) => $query->where('cooperative_id', $cooperativeId))
                ->orderBy('group')
                ->orderBy('key');

            if ($publicOnly) {
                $query->where('is_public', true);
            }

            return $query->get()
                ->groupBy('group')
                ->map(fn ($settings) => $settings
                    ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $setting->typedValue()])
                    ->all())
                ->all();
        });
    }

    public function group(string $group, ?int $cooperativeId = null, bool $publicOnly = false): array
    {
        return $this->grouped($cooperativeId, $publicOnly)[$group] ?? [];
    }

    public function shared(): array
    {
        $cooperative = $this->activeCooperative();
        $settings = $this->grouped($cooperative?->id, true);

        return [
            'cooperative' => [
                'name' => Arr::get($settings, 'brand.name', $cooperative?->name ?? config('app.name')),
                'short_name' => Arr::get($settings, 'brand.short_name', $cooperative?->short_name),
                'registration_no' => Arr::get($settings, 'brand.registration_no', $cooperative?->registration_no),
                'logo_path' => Arr::get($settings, 'brand.logo_path', $cooperative?->logo_path),
                'primary_color' => Arr::get($settings, 'brand.primary_color', $cooperative?->primary_color),
                'secondary_color' => Arr::get($settings, 'brand.secondary_color', $cooperative?->secondary_color),
            ],
            'contact' => $settings['contact'] ?? [],
            'social' => $settings['social'] ?? [],
            'system' => $settings['system'] ?? [],
        ];
    }

    public function update(Cooperative $cooperative, array $groups): void
    {
        foreach ($this->definitions() as $group => $definitions) {
            foreach ($definitions as $key => $definition) {
                if (! Arr::has($groups, "{$group}.{$key}")) {
                    continue;
                }

                $value = Arr::get($groups, "{$group}.{$key}");

                Setting::query()->updateOrCreate([
                    'cooperative_id' => $cooperative->id,
                    'group' => $group,
                    'key' => $key,
                ], [
                    'value' => $this->serializeValue($value, $definition['type']),
                    'type' => $definition['type'],
                    'is_public' => $definition['public'],
                ]);
            }
        }

        $this->syncCooperativeProfile($cooperative, $groups);
        $this->clearCache();
    }

    public function definitions(): array
    {
        return [
            'brand' => [
                'name' => ['type' => 'string', 'public' => true],
                'short_name' => ['type' => 'string', 'public' => true],
                'registration_no' => ['type' => 'string', 'public' => true],
                'logo_path' => ['type' => 'image', 'public' => true],
                'primary_color' => ['type' => 'color', 'public' => true],
                'secondary_color' => ['type' => 'color', 'public' => true],
            ],
            'contact' => [
                'address_line_1' => ['type' => 'string', 'public' => true],
                'address_line_2' => ['type' => 'string', 'public' => true],
                'city' => ['type' => 'string', 'public' => true],
                'state' => ['type' => 'string', 'public' => true],
                'postcode' => ['type' => 'string', 'public' => true],
                'country' => ['type' => 'string', 'public' => true],
                'phone' => ['type' => 'string', 'public' => true],
                'email' => ['type' => 'email', 'public' => true],
                'whatsapp' => ['type' => 'string', 'public' => true],
                'website_url' => ['type' => 'url', 'public' => true],
            ],
            'social' => [
                'facebook_url' => ['type' => 'url', 'public' => true],
                'instagram_url' => ['type' => 'url', 'public' => true],
                'linkedin_url' => ['type' => 'url', 'public' => true],
            ],
            'seo' => [
                'meta_title' => ['type' => 'string', 'public' => true],
                'meta_description' => ['type' => 'text', 'public' => true],
            ],
            'system' => [
                'timezone' => ['type' => 'string', 'public' => false],
                'date_format' => ['type' => 'string', 'public' => false],
            ],
        ];
    }

    public function clearCache(): void
    {
        Cache::forget('settings.active_cooperative');
        Cache::forget('settings.active_cooperative_id');

        Cooperative::query()->pluck('id')->each(function (int $cooperativeId): void {
            Cache::forget("settings.grouped.all.{$cooperativeId}");
            Cache::forget("settings.grouped.public.{$cooperativeId}");
        });

        Cache::forget('settings.grouped.all.');
        Cache::forget('settings.grouped.public.');
    }

    private function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $type === 'json' ? json_encode($value) : (string) $value;
    }

    private function syncCooperativeProfile(Cooperative $cooperative, array $groups): void
    {
        $brand = $groups['brand'] ?? [];
        $contact = $groups['contact'] ?? [];
        $social = $groups['social'] ?? [];

        $cooperative->forceFill([
            'name' => $brand['name'] ?? $cooperative->name,
            'short_name' => $brand['short_name'] ?? null,
            'registration_no' => $brand['registration_no'] ?? null,
            'logo_path' => $brand['logo_path'] ?? null,
            'primary_color' => $brand['primary_color'] ?? null,
            'secondary_color' => $brand['secondary_color'] ?? null,
            'address_line_1' => $contact['address_line_1'] ?? null,
            'address_line_2' => $contact['address_line_2'] ?? null,
            'city' => $contact['city'] ?? null,
            'state' => $contact['state'] ?? null,
            'postcode' => $contact['postcode'] ?? null,
            'country' => $contact['country'] ?? 'Malaysia',
            'phone' => $contact['phone'] ?? null,
            'email' => $contact['email'] ?? null,
            'whatsapp' => $contact['whatsapp'] ?? null,
            'website_url' => $contact['website_url'] ?? null,
            'facebook_url' => $social['facebook_url'] ?? null,
            'instagram_url' => $social['instagram_url'] ?? null,
            'linkedin_url' => $social['linkedin_url'] ?? null,
        ])->save();
    }
}
