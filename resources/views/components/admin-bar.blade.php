@props(['editUrl' => null, 'editLabel' => 'Edit in dashboard', 'highlight' => false])

{{-- Shown only to signed-in people with a reason to see it: previews, and the owner's shortcut back to the dashboard. --}}
<div @class([
    'border-b text-sm',
    'border-accent bg-accent/20 text-ink' => $highlight,
    'border-line bg-surface text-muted' => ! $highlight,
])>
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6">
        <p>{{ $slot }}</p>
        @if ($editUrl)
            <a href="{{ $editUrl }}" class="font-semibold underline underline-offset-2">{{ $editLabel }}</a>
        @endif
    </div>
</div>
