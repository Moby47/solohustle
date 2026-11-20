@props(['title' => null, 'description' => null, 'noindex' => false])

@php($siteName = setting('general.site_name'))
@php($faviconUrl = media_url(setting('general.favicon')))

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | '.setting('seo.title_suffix', $siteName) : $siteName }}</title>
    <meta name="description" content="{{ $description ?: setting('seo.default_meta_description') }}">
    @if ($noindex || setting('seo.discourage_search_engines'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    @if ($faviconUrl)
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2">
        Skip to content
    </a>

    <x-site-header />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-site-footer />
</body>
</html>
