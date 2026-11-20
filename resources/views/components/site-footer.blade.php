{{-- Footer link groups and social links are filled from the Navigation settings in C5. --}}
<footer class="border-t border-line bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <x-logo />
        <p class="mt-2 text-sm text-muted">Work. Write. Build.</p>
        <p class="mt-8 text-xs text-muted">&copy; {{ now()->year }} {{ config('app.name') }}</p>
    </div>
</footer>
