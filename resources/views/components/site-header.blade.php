@php
    use App\Support\NavLink;

    $menu = setting('navigation.header', []);
    $ctaLabel = setting('navigation.cta_label');
    $ctaLink = setting('navigation.cta_link');
@endphp

<header class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6">
        <x-logo />

        @if ($menu)
            <nav aria-label="Main" class="hidden lg:block">
                <ul class="flex items-center gap-1">
                    @foreach ($menu as $item)
                        @php($active = NavLink::isActive($item['link']))
                        <li>
                            <a href="{{ NavLink::href($item['link']) }}"
                               @if ($active) aria-current="page" @endif
                               @class([
                                   'rounded-md px-3 py-2 text-sm font-medium no-underline transition-colors',
                                   'bg-primary-light text-primary' => $active,
                                   'text-ink hover:bg-surface hover:text-primary' => ! $active,
                               ])>{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div class="flex items-center gap-2">
            @if (filled($ctaLabel) && filled($ctaLink))
                <a href="{{ NavLink::href($ctaLink) }}" class="hidden rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white no-underline transition-colors hover:bg-primary-dark sm:inline-flex">
                    {{ $ctaLabel }}
                </a>
            @endif

            @if ($menu)
                <button type="button" data-menu-open aria-controls="mobile-menu" aria-expanded="false"
                        class="inline-flex size-10 items-center justify-center rounded-md text-ink hover:bg-surface lg:hidden">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <span class="sr-only">Open menu</span>
                </button>
            @endif
        </div>
    </div>

    @if ($menu)
        {{-- Mobile slide-out menu, opened by resources/js/app.js. --}}
        <div id="mobile-menu" data-menu class="fixed inset-0 z-50 hidden lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
            <div data-menu-close class="absolute inset-0 bg-ink/40"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-xs flex-col bg-white shadow-xl">
                <div class="flex h-16 items-center justify-between border-b border-line px-4">
                    <x-logo />
                    <button type="button" data-menu-close class="inline-flex size-10 items-center justify-center rounded-md text-ink hover:bg-surface">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span class="sr-only">Close menu</span>
                    </button>
                </div>
                <nav aria-label="Main" class="flex-1 overflow-y-auto px-2 py-4">
                    <ul class="space-y-1">
                        @foreach ($menu as $item)
                            @php($active = NavLink::isActive($item['link']))
                            <li>
                                <a href="{{ NavLink::href($item['link']) }}"
                                   @if ($active) aria-current="page" @endif
                                   @class([
                                       'block rounded-md px-3 py-3 text-base font-medium no-underline',
                                       'bg-primary-light text-primary' => $active,
                                       'text-ink hover:bg-surface' => ! $active,
                                   ])>{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                @if (filled($ctaLabel) && filled($ctaLink))
                    <div class="border-t border-line p-4">
                        <a href="{{ NavLink::href($ctaLink) }}" class="block rounded-md bg-primary px-4 py-3 text-center font-semibold text-white no-underline hover:bg-primary-dark">
                            {{ $ctaLabel }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</header>
