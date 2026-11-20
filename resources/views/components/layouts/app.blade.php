@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'canonical' => null,
    'noindex' => false,
    'schemas' => [],
])

@php
    $faviconUrl = media_url(setting('general.favicon'));
    // Analytics only on the live site, only when an ID is set, and only after the visitor agrees (see the consent bar).
    $analyticsId = app()->isProduction() ? setting('integrations.ga4_id') : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo :title="$title" :description="$description" :image="$image" :type="$type" :canonical="$canonical" :noindex="$noindex" />
    @if ($faviconUrl)
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-json-ld :data="\App\Support\StructuredData::site()" />
    @foreach (array_filter($schemas) as $schema)
        <x-json-ld :data="$schema" />
    @endforeach
    @if ($analyticsId)
        <script>window.siteAnalyticsId = @js($analyticsId);</script>
    @endif
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2">
        Skip to content
    </a>

    <x-site-header />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-site-footer :analytics="(bool) $analyticsId" />

    @if ($analyticsId)
        <x-cookie-consent />
    @endif
    @stack('scripts')
</body>
</html>
