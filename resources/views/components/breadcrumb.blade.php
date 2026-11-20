@props(['items'])

{{-- $items: label => url; the last item is the current page and is not linked. --}}
<nav aria-label="Breadcrumb" {{ $attributes->class('text-sm text-muted') }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="{{ route('home') }}" class="text-muted no-underline hover:text-primary">Home</a></li>
        @foreach ($items as $label => $url)
            <li aria-hidden="true" class="text-line">/</li>
            <li>
                @if ($loop->last)
                    <span aria-current="page" class="text-ink">{{ $label }}</span>
                @elseif (blank($url))
                    <span>{{ $label }}</span>
                @else
                    <a href="{{ $url }}" class="text-muted no-underline hover:text-primary">{{ $label }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
