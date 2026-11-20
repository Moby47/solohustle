<x-layouts.app title="Page not found" :noindex="true">
    <section class="mx-auto max-w-2xl px-4 py-20 text-center sm:px-6 sm:py-28">
        <p class="text-sm font-semibold uppercase tracking-wider text-primary">404</p>
        <h1 class="mt-3 text-3xl sm:text-4xl">We couldn't find that page</h1>
        <p class="mt-4 text-lg text-muted">It may have moved, or the link may be mistyped. Try one of these instead:</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-3">
            <a href="{{ route('home') }}" class="rounded-md bg-primary px-5 py-3 font-semibold text-white no-underline hover:bg-primary-dark">Go to the homepage</a>
            <a href="{{ url('/contact') }}" class="font-semibold">Contact us</a>
        </div>
    </section>
</x-layouts.app>
