@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'canonical' => null,
    'noindex' => false,
])

{{-- Everything search engines and social networks read about a page. One per page, in the <head>. --}}
@php
    $siteName = setting('general.site_name');
    $fullTitle = $title ? $title.' | '.setting('seo.title_suffix', $siteName) : $siteName;
    $metaDescription = \Illuminate\Support\Str::limit(trim(strip_tags($description ?: setting('seo.default_meta_description'))), 160);
    $shareImage = $image ?: media_url(setting('seo.default_share_image')) ?: media_url(setting('general.logo'));
    $page = request()->integer('page');
    $canonicalUrl = $canonical ?: url()->current().($page > 1 ? '?page='.$page : '');
    $hideFromSearch = $noindex || setting('seo.discourage_search_engines');
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
@if ($hideFromSearch)
    <meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title ?: $siteName }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
@if ($shareImage)
    <meta property="og:image" content="{{ $shareImage }}">
@endif
<meta name="twitter:card" content="{{ $shareImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title ?: $siteName }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if ($shareImage)
    <meta name="twitter:image" content="{{ $shareImage }}">
@endif

@if (filled(setting('integrations.search_console_code')))
    <meta name="google-site-verification" content="{{ setting('integrations.search_console_code') }}">
@endif
@if (filled(setting('ai.bing_verification')))
    <meta name="msvalidate.01" content="{{ setting('ai.bing_verification') }}">
@endif
