@props(['analytics' => false])

@php
    use App\Support\NavLink;

    $groups = setting('navigation.footer', []);
    $socials = setting('social.links', []);
    $platforms = config('settings.social_platforms');
@endphp

<footer class="border-t border-line bg-surface">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.4fr_repeat(3,1fr)]">
        <div>
            <x-logo />
            @if (filled(setting('general.tagline')))
                <p class="mt-3 text-sm text-muted">{{ setting('general.tagline') }}</p>
            @endif

            @if ($socials)
                <ul class="mt-5 flex flex-wrap gap-2" aria-label="Follow us">
                    @foreach ($socials as $social)
                        <li>
                            <a href="{{ $social['url'] }}" rel="me noopener" target="_blank"
                               class="inline-flex rounded-full border border-line bg-white px-3 py-1 text-xs font-medium text-ink no-underline transition-colors hover:border-primary hover:text-primary">
                                {{ $platforms[$social['platform']] ?? ucfirst($social['platform']) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @foreach ($groups as $group)
            <nav aria-label="{{ $group['title'] }}">
                <h2 class="text-sm font-semibold text-ink">{{ $group['title'] }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($group['links'] ?? [] as $link)
                        <li>
                            <a href="{{ NavLink::href($link['link']) }}" class="text-sm text-muted no-underline hover:text-primary">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>

    <div class="border-t border-line">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-5 text-xs text-muted sm:px-6">
            <p>&copy; {{ now()->year }} {{ setting('general.site_name') }}</p>
            @if ($analytics)
                <button type="button" data-cookie-settings class="underline underline-offset-2 hover:text-primary">Cookie settings</button>
            @endif
        </div>
    </div>
</footer>
