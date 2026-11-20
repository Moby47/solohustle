<x-layouts.app :title="$query !== '' ? 'Search: '.$query : 'Search'" :noindex="true">
    <section class="border-b border-line bg-surface">
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
            <h1 class="text-3xl sm:text-4xl">Search</h1>
            <form method="GET" action="{{ route('search') }}" role="search" class="mt-6 flex gap-2">
                <label for="search-q" class="sr-only">Search jobs and articles</label>
                <input id="search-q" type="search" name="q" value="{{ $query }}" placeholder="Search jobs and articles" @if ($query === '') autofocus @endif
                       class="block w-full rounded-md border border-line bg-white px-4 py-3 text-ink placeholder:text-muted focus:border-primary focus:outline-none">
                <button type="submit" class="shrink-0 rounded-md bg-primary px-5 py-3 font-semibold text-white hover:bg-primary-dark">Search</button>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        @if ($query === '')
            <p class="text-muted">Search for a job title, a company or a topic, or browse:</p>
            <ul class="mt-4 flex flex-wrap gap-2">
                <li><a href="{{ url('/jobs') }}" class="inline-flex rounded-full bg-primary px-3 py-1.5 text-sm font-medium text-white no-underline hover:bg-primary-dark">All jobs</a></li>
                @foreach ($categories as $category)
                    <li><a href="{{ url('/'.$category->slug) }}" class="inline-flex rounded-full border border-line px-3 py-1.5 text-sm font-medium text-ink no-underline hover:border-primary hover:text-primary">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        @elseif ($jobs->isEmpty() && $posts->isEmpty())
            <div class="rounded-xl border border-dashed border-line px-6 py-14 text-center">
                <h2 class="text-xl">Nothing found for "{{ $query }}"</h2>
                <p class="mt-2 text-muted">Check the spelling, try a shorter or more general word, or browse instead.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <a href="{{ url('/jobs') }}" class="inline-flex rounded-md bg-primary px-5 py-2.5 font-semibold text-white no-underline hover:bg-primary-dark">Browse all jobs</a>
                    @foreach ($categories as $category)
                        <a href="{{ url('/'.$category->slug) }}" class="inline-flex rounded-md border border-line px-4 py-2.5 text-sm font-medium text-ink no-underline hover:border-primary hover:text-primary">{{ $category->name }}</a>
                    @endforeach
                </div>
            </div>
        @else
            @if ($jobs->isNotEmpty())
                <section aria-labelledby="job-results">
                    <div class="flex items-end justify-between gap-4">
                        <h2 id="job-results" class="text-2xl">Jobs</h2>
                        <a href="{{ url('/jobs').'?'.http_build_query(['q' => $query]) }}" class="text-sm font-semibold">See all matching jobs</a>
                    </div>
                    <ul class="mt-4 space-y-3">
                        @foreach ($jobs as $job)
                            <li><x-job-card :job="$job" /></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($posts->isNotEmpty())
                <section aria-labelledby="article-results" @class(['mt-12' => $jobs->isNotEmpty()])>
                    <h2 id="article-results" class="text-2xl">Articles</h2>
                    <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($posts as $post)
                            <x-article-card :post="$post" />
                        @endforeach
                    </div>
                    <div class="mt-10">
                        {{ $posts->links('pagination.site') }}
                    </div>
                </section>
            @endif
        @endif
    </div>
</x-layouts.app>
