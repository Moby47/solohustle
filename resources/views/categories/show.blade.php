<x-layouts.app :title="$category->seo_title ?: $category->name" :description="$category->meta_description ?: $category->description">
    <section class="border-b border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
            <x-breadcrumb :items="[$category->name => null]" />
            <h1 class="mt-4 text-3xl sm:text-5xl">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-4 max-w-2xl text-lg text-muted">{{ $category->description }}</p>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        @if ($posts->isEmpty())
            <div class="rounded-xl border border-dashed border-line px-6 py-16 text-center">
                <h2 class="text-xl">No articles here yet</h2>
                <p class="mt-2 text-muted">New guides are on the way. In the meantime, why not write one?</p>
                <a href="{{ route('publish') }}" class="mt-6 inline-flex rounded-md bg-primary px-5 py-3 font-semibold text-white no-underline hover:bg-primary-dark">Publish with us</a>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-article-card :post="$post" :show-category="false" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $posts->links('pagination.site') }}
            </div>
        @endif
    </section>
</x-layouts.app>
