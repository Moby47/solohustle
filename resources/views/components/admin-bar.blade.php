@props(['editUrl', 'draft' => false])

{{-- Only rendered for the signed-in owner: a quick way back to the dashboard. --}}
<div @class([
    'border-b text-sm',
    'border-accent bg-accent/20 text-ink' => $draft,
    'border-line bg-surface text-muted' => ! $draft,
])>
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6">
        <p>
            @if ($draft)
                <strong class="font-semibold">Draft preview.</strong> Only you can see this. Publish it in the dashboard to make it public.
            @else
                You're signed in as the owner.
            @endif
        </p>
        <a href="{{ $editUrl }}" class="font-semibold underline underline-offset-2">Edit in dashboard</a>
    </div>
</div>
