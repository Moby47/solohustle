<x-layouts.app :title="$author->name" :description="$author->headline ?: 'Articles by '.$author->name">
    <x-json-ld :data="$personSchema" />

    <section class="border-b border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
            <x-breadcrumb :items="['Authors' => null, $author->name => null]" />

            <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-center">
                <x-avatar :user="$author" :size="112" class="ring-4 ring-white" />
                <div>
                    <h1 class="text-3xl sm:text-4xl">{{ $author->name }}</h1>
                    @if ($author->headline)
                        <p class="mt-1 text-lg text-muted">{{ $author->headline }}</p>
                    @endif
                    @php($links = array_filter(['Website' => $author->website_url, 'LinkedIn' => $author->linkedin_url, 'X' => $author->x_url]))
                    @if ($links)
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($links as $label => $url)
                                <li>
                                    <a href="{{ $url }}" rel="me noopener" target="_blank"
                                       class="inline-flex rounded-full border border-line bg-white px-3 py-1 text-sm font-medium text-ink no-underline hover:border-primary hover:text-primary">{{ $label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @if ($author->bio)
                <p class="mt-6 max-w-3xl text-ink">{{ $author->bio }}</p>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <h2 class="text-2xl">Articles by {{ $author->name }}</h2>
        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <x-article-card :post="$post" />
            @endforeach
        </div>
        <div class="mt-10">
            {{ $posts->links() }}
        </div>
    </section>
</x-layouts.app>
