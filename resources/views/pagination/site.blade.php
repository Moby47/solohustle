{{-- Real links (crawlable, work without JavaScript); inside Livewire they also update in place. --}}
@php($livewire = $livewire ?? false)

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pages" class="flex items-center justify-between gap-2 text-sm">
        @if ($paginator->onFirstPage())
            <span class="rounded-md border border-line px-3 py-2 text-muted opacity-50">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               @if ($livewire) wire:click.prevent="previousPage" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" @endif
               class="rounded-md border border-line px-3 py-2 font-medium text-ink no-underline hover:border-primary hover:text-primary">Previous</a>
        @endif

        <ul class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="px-2 text-muted">{{ $element }}</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex min-w-9 justify-center rounded-md bg-primary px-3 py-2 font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                   @if ($livewire) wire:click.prevent="gotoPage({{ $page }})" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" @endif
                                   class="inline-flex min-w-9 justify-center rounded-md px-3 py-2 text-ink no-underline hover:bg-surface">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        <span class="text-muted sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               @if ($livewire) wire:click.prevent="nextPage" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })" @endif
               class="rounded-md border border-line px-3 py-2 font-medium text-ink no-underline hover:border-primary hover:text-primary">Next</a>
        @else
            <span class="rounded-md border border-line px-3 py-2 text-muted opacity-50">Next</span>
        @endif
    </nav>
@endif
