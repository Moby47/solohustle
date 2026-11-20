@props(['post', 'showCategory' => true])

<article class="group relative flex flex-col overflow-hidden rounded-xl border border-line bg-white transition-shadow hover:shadow-md">
    @if ($post->cover_image)
        <img src="{{ $post->coverUrl(480) }}"
             srcset="{{ $post->coverUrl(480) }} 480w, {{ $post->coverUrl(960) }} 960w"
             sizes="(min-width: 1024px) 360px, (min-width: 640px) 50vw, 100vw"
             width="480" height="270" loading="lazy" alt="{{ $post->cover_alt }}"
             class="aspect-video w-full object-cover">
    @else
        <div class="flex aspect-video w-full items-end bg-gradient-to-br from-primary-light to-secondary-light p-4" aria-hidden="true">
            <span class="text-sm font-semibold text-primary">{{ $post->category->name }}</span>
        </div>
    @endif

    <div class="flex flex-1 flex-col p-5">
        @if ($showCategory)
            <p class="text-xs font-semibold uppercase tracking-wider text-primary">{{ $post->category->name }}</p>
        @endif
        <h3 class="mt-2 text-lg leading-snug">
            <a href="{{ $post->url() }}" class="text-ink no-underline after:absolute after:inset-0 group-hover:text-primary">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="mt-2 line-clamp-3 text-sm text-muted">{{ $post->excerpt }}</p>
        @endif
        <p class="mt-auto flex items-center gap-2 pt-4 text-xs text-muted">
            <x-avatar :user="$post->author" :size="24" />
            <span>{{ $post->author->name }}</span>
            <span aria-hidden="true">·</span>
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j M Y') }}</time>
            <span aria-hidden="true">·</span>
            <span>{{ $post->reading_minutes }} min read</span>
        </p>
    </div>
</article>
