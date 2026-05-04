<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'KoperasiHub') }}</title>

        @php
            try {
                $faviconPath = app(\App\Services\Settings\SettingsService::class)->activeCooperative()?->favicon_path;
                $faviconUrl = $faviconPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($faviconPath) : null;
            } catch (\Throwable) {
                $faviconUrl = null;
            }
        @endphp

        @if($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @else
            <link rel="icon" href="/favicon.ico">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-950 antialiased">
        @inertia
    </body>
</html>
