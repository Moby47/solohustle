@php($logoUrl = media_url(setting('general.logo')))
@php($siteName = setting('general.site_name'))

<a href="{{ route('home') }}" {{ $attributes->class('inline-flex items-baseline gap-0.5 text-xl font-extrabold tracking-tight text-primary no-underline') }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-10 w-auto">
    @else
        {{-- Text logo until the owner uploads one in Site settings. --}}
        <span>{{ $siteName }}</span><span class="text-accent" aria-hidden="true">.</span>
    @endif
</a>
