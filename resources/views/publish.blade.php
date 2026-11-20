@php
    $signUpUrl = url('/writer/register');
    $ctaLabel = setting('publish.cta_label');
    $benefits = setting('publish.benefits', []);
    $steps = setting('publish.steps', []);
@endphp

<x-layouts.app :title="setting('publish.headline')" :description="setting('publish.intro')">
    <section class="bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
            <p class="text-sm font-semibold uppercase tracking-wider text-primary">For writers</p>
            <h1 class="mt-3 max-w-3xl text-4xl leading-tight sm:text-5xl">{{ setting('publish.headline') }}</h1>
            @if (filled(setting('publish.intro')))
                <p class="mt-5 max-w-2xl text-lg text-muted">{{ setting('publish.intro') }}</p>
            @endif
            <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
                <a href="{{ $signUpUrl }}" class="rounded-md bg-primary px-6 py-3 font-semibold text-white no-underline hover:bg-primary-dark">{{ $ctaLabel }}</a>
                <a href="{{ url('/writer/login') }}" class="font-semibold">Already a writer? Sign in</a>
            </div>
        </div>
    </section>

    @if ($benefits)
        <section aria-labelledby="why" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 id="why" class="text-2xl sm:text-3xl">Why write for us</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                @foreach ($benefits as $benefit)
                    <div class="rounded-xl border border-line p-6">
                        <span class="block h-1 w-10 rounded-full bg-accent" aria-hidden="true"></span>
                        <h3 class="mt-4 text-lg">{{ $benefit['title'] }}</h3>
                        <p class="mt-2 text-muted">{{ $benefit['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($steps)
        <section aria-labelledby="how" class="border-y border-line bg-surface">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 id="how" class="text-2xl sm:text-3xl">How it works</h2>
                <ol class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($steps as $step)
                        <li class="rounded-xl bg-white p-6">
                            <span class="inline-flex size-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">{{ $loop->iteration }}</span>
                            <h3 class="mt-4 text-lg">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-muted">{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <section class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6">
        <h2 class="text-2xl sm:text-3xl">Ready to share what you know?</h2>
        <p class="mt-3 text-muted">
            Please read our <a href="{{ url('/creator-guidelines') }}">Creator Guidelines</a> first: they explain what we publish and how to write for search and AI.
        </p>
        <a href="{{ $signUpUrl }}" class="mt-8 inline-flex rounded-md bg-primary px-6 py-3 font-semibold text-white no-underline hover:bg-primary-dark">{{ $ctaLabel }}</a>
    </section>
</x-layouts.app>
