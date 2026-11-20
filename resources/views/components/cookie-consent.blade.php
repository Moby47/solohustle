{{-- Shown by resources/js/app.js until the visitor chooses; Google Analytics loads only after "Accept". --}}
<div data-cookie-consent hidden role="region" aria-label="Cookie choice" class="fixed inset-x-0 bottom-0 z-50 p-3 sm:p-4">
    <div class="mx-auto flex max-w-3xl flex-col gap-3 rounded-xl border border-line bg-white p-4 shadow-lg sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-ink">
            May we use analytics cookies to see how people use the site? It helps us improve it.
            <a href="{{ url('/privacy-policy') }}">Privacy policy</a>
        </p>
        <div class="flex shrink-0 gap-2">
            <button type="button" data-consent="denied" class="rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink hover:bg-surface">Decline</button>
            <button type="button" data-consent="granted" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">Accept</button>
        </div>
    </div>
</div>
