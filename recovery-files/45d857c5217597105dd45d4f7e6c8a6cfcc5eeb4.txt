<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PostcodeService
{
    public function lookup(string $code): ?array
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 5) return null;

        $exact = config("poskod.exact.{$code}");
        if ($exact) {
            return [...$exact, 'country' => 'Malaysia'];
        }

        $prefix = substr($code, 0, 2);
        $prefixMatch = config("poskod.by_prefix.{$prefix}");
        if ($prefixMatch) {
            return ['city' => null, 'state' => $prefixMatch['state'], 'country' => 'Malaysia'];
        }

        return $this->lookupExternal($code);
    }

    private function lookupExternal(string $code): ?array
    {
        $cacheKey = "poskod_{$code}";
        if (Cache::has($cacheKey)) return Cache::get($cacheKey);

        try {
            $url = config('poskod.api_url') . '/' . $code;
            $response = Http::timeout(3)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                $city = $data['city'] ?? $data['bandar'] ?? null;
                $state = $data['state'] ?? $data['negeri'] ?? null;
                if ($state) {
                    $result = ['city' => $city, 'state' => $state, 'country' => 'Malaysia'];
                    Cache::put($cacheKey, $result, now()->addDays(30));
                    return $result;
                }
            }
        } catch (\Exception) {}

        Cache::put($cacheKey, null, now()->addDays(1));
        return null;
    }
}
