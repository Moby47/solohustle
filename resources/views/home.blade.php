@php
    use App\Support\NavLink;

    $hero = $sections['hero'];
    $creator = $sections['creator'];
    $resource = $sections['resource'];
    $newsletter = $sections['newsletter'];
@endphp

<x-layouts.app>
    @if ($hero['visible'])
        <section class="relative overflow-hidden bg-surface">
            <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 size-80 rounded-full bg-secondary/25 blur-3xl"></div>
            <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                @if (filled(setting('general.tagline')))
                    <p class="text-sm font-semibold uppercase tracking-wider text-primary">{{ setting('general.tagline') }}</p>
                @endif
                <h1 class="mt-3 max-w-3xl text-4xl leading-tight sm:text-6xl sm:leading-[1.05]">{{ $hero['headline'] }}</h1>
                @if (filled($hero['text']))
                    <p class="mt-5 max-w-xl text-lg text-muted sm:text-xl">{{ $hero['text'] }}</p>
                @endif
                <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
                    @if (filled($hero['primary_label']) && filled($hero['primary_link']))
                        <a href="{{ NavLink::href($hero['primary_link']) }}" class="rounded-md bg-primary px-6 py-3 font-semibold text-white no-underline hover:bg-primary-dark">{{ $hero['primary_label'] }}</a>
                    @endif
                    @if (filled($hero['secondary_label']) && filled($hero['secondary_link']))
                        <a href="{{ NavLink::href($hero['secondary_link']) }}" class="font-semibold">{{ $hero['secondary_label'] }} <span aria-hidden="true">→</span></a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($sections['jobs']['visible'] && $jobs->isNotEmpty())
        <section aria-labelledby="home-jobs" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 id="home-jobs" class="text-2xl sm:text-3xl">{{ $sections['jobs']['title'] }}</h2>
                <a href="{{ url('/jobs') }}" class="text-sm font-semibold">See all jobs</a>
            </div>

            <form method="GET" action="{{ url('/jobs') }}" role="search" class="mt-6 flex flex-col gap-2 rounded-xl border border-line bg-white p-2 sm:flex-row">
                <label for="home-job-q" class="sr-only">Job title, company or keyword</label>
                <input id="home-job-q" type="search" name="q" placeholder="Job title, company or keyword"
                       class="block w-full rounded-md px-3 py-2.5 text-ink placeholder:text-muted focus:outline-none">
                <label for="home-job-category" class="sr-only">Category</label>
                <select id="home-job-category" name="category" class="rounded-md border border-line bg-white px-3 py-2.5 text-sm text-ink sm:w-56">
                    <option value="">All categories</option>
                    @foreach ($jobCategories as $jobCategory)
                        <option value="{{ $jobCategory->slug }}">{{ $jobCategory->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="shrink-0 rounded-md border border-primary px-5 py-2.5 font-semibold text-primary hover:bg-primary hover:text-white">Find jobs</button>
            </form>

            <ul class="mt-6 grid gap-3 lg:grid-cols-2">
                @foreach ($jobs as $job)
                    <li><x-job-card :job="$job" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($sections['guides']['visible'] && $featured->isNotEmpty())
        <section aria-labelledby="home-guides" class="border-y border-line bg-surface">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 id="home-guides" class="text-2xl sm:text-3xl">{{ $sections['guides']['title'] }}</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featured as $post)
                        <x-article-card :post="$post" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($creator['visible'])
        <section aria-labelledby="home-creator" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="grid items-center gap-8 rounded-2xl bg-primary px-6 py-10 text-white sm:px-10 md:grid-cols-[1fr_auto]">
                <div>
                    <h2 id="home-creator" class="text-2xl text-white sm:text-3xl">{{ $creator['title'] }}</h2>
                    @if (filled($creator['text']))
                        <p class="mt-3 max-w-2xl text-white/85">{{ $creator['text'] }}</p>
                    @endif
                </div>
                <a href="{{ route('publish') }}" class="inline-flex justify-center rounded-md bg-white px-5 py-3 font-semibold text-primary no-underline hover:bg-accent hover:text-ink">How it works</a>
            </div>
        </section>
    @endif

    @if ($sections['articles']['visible'] && $latestPosts->isNotEmpty())
        <section aria-labelledby="home-articles" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <h2 id="home-articles" class="text-2xl sm:text-3xl">{{ $sections['articles']['title'] }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($latestPosts as $post)
                    <x-article-card :post="$post" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($resource['visible'] && filled($resource['title']))
        <section aria-labelledby="home-resource" class="border-t border-line bg-secondary-light">
            <div @class([
                'mx-auto grid max-w-6xl items-center gap-8 px-4 py-16 sm:px-6',
                'md:grid-cols-2' => filled($resource['image']),
            ])>
                @if (filled($resource['image']))
                    <img src="{{ media_url($resource['image']) }}" alt="" loading="lazy" class="w-full rounded-xl object-cover">
                @endif
                <div>
                    <h2 id="home-resource" class="text-2xl sm:text-3xl">{{ $resource['title'] }}</h2>
                    @if (filled($resource['text']))
                        <p class="mt-3 text-lg text-muted">{{ $resource['text'] }}</p>
                    @endif
                    @if (filled($resource['link_label']) && filled($resource['link']))
                        <a href="{{ NavLink::href($resource['link']) }}" class="mt-6 inline-flex font-semibold">{{ $resource['link_label'] }} <span aria-hidden="true" class="ml-1">→</span></a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($newsletter['visible'] && Route::has('newsletter.subscribe'))
        <section aria-labelledby="home-newsletter" class="border-t border-line">
            <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6">
                <h2 id="home-newsletter" class="text-2xl sm:text-3xl">{{ $newsletter['title'] }}</h2>
                @if (filled($newsletter['text']))
                    <p class="mt-3 text-muted">{{ $newsletter['text'] }}</p>
                @endif
                <x-newsletter-form source="homepage" class="mx-auto mt-6 max-w-md" />
            </div>
        </section>
    @endif
</x-layouts.app>
