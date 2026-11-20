@php
    use App\Support\NavLink;

    $updatedAt = $post->displayUpdatedAt();
    $showUpdated = $post->content_updated_at && $post->published_at && ! $post->content_updated_at->isSameDay($post->published_at);
    $midCtaText = setting('article.mid_cta_text');
    $midCtaLabel = setting('article.mid_cta_label');
    $midCtaLink = setting('article.mid_cta_link');
@endphp

<x-layouts.app :title="$post->seo_title ?: $post->title" :description="$post->meta_description ?: $post->excerpt" :noindex="! $isLive"
                type="article" :image="$post->coverUrl()" :canonical="$post->url()"
                :schemas="[
                    \App\Support\StructuredData::article($post, $authorHasPage),
                    \App\Support\StructuredData::breadcrumbs([$post->category->name => $post->category->url(), $post->title => $post->url()]),
                    \App\Support\StructuredData::faq($post),
                ]">
    @if (! $isLive)
        <x-admin-bar :highlight="true"
                     :edit-url="$isAdmin ? \App\Filament\Resources\Posts\PostResource::getUrl('edit', ['record' => $post], panel: 'admin') : $post->writerUrl()"
                     :edit-label="$isAdmin ? 'Edit in dashboard' : 'Back to my article'">
            <strong class="font-semibold">Preview.</strong>
            @if ($post->status === \App\Enums\PostStatus::Published)
                Scheduled for {{ $post->published_at->format('j M Y, H:i') }}. Visitors can't see it yet.
            @else
                This article isn't published, so only you{{ $isAdmin ? '' : ' and the editor' }} can see it.
            @endif
        </x-admin-bar>
    @elseif ($isAdmin)
        <x-admin-bar :edit-url="\App\Filament\Resources\Posts\PostResource::getUrl('edit', ['record' => $post], panel: 'admin')">
            You're signed in as the owner.
        </x-admin-bar>
    @endif

    <article>
        <header class="mx-auto max-w-3xl px-4 pt-10 sm:px-6 sm:pt-14">
            <x-breadcrumb :items="[$post->category->name => $post->category->url(), $post->title => null]" />

            <a href="{{ $post->category->url() }}" class="mt-6 inline-block text-sm font-semibold uppercase tracking-wider text-primary no-underline hover:underline">
                {{ $post->category->name }}
            </a>
            <h1 class="mt-2 text-3xl leading-tight sm:text-5xl sm:leading-[1.1]">{{ $post->title }}</h1>

            @if ($post->excerpt)
                <p class="mt-4 text-lg text-muted sm:text-xl">{{ $post->excerpt }}</p>
            @endif

            <div class="mt-6 flex items-center gap-3 text-sm text-muted">
                <x-avatar :user="$post->author" :size="44" />
                <div>
                    <p class="font-semibold text-ink">
                        @if ($authorHasPage)
                            <a href="{{ $post->author->authorUrl() }}" rel="author" class="text-ink no-underline hover:text-primary">{{ $post->author->name }}</a>
                        @else
                            {{ $post->author->name }}
                        @endif
                    </p>
                    <p class="flex flex-wrap gap-x-2">
                        @if ($post->published_at)
                            <span>Published <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('j M Y') }}</time></span>
                        @endif
                        @if ($showUpdated)
                            <span aria-hidden="true">·</span>
                            <span>Updated <time datetime="{{ $updatedAt->toIso8601String() }}">{{ $updatedAt->format('j M Y') }}</time></span>
                        @endif
                        <span aria-hidden="true">·</span>
                        <span>{{ $post->reading_minutes }} min read</span>
                    </p>
                </div>
            </div>

            @if ($post->quick_answer)
                <aside aria-label="Quick answer" class="mt-8 rounded-xl border-l-4 border-primary bg-primary-light px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-primary">Quick answer</p>
                    <p class="mt-1 text-ink">{{ $post->quick_answer }}</p>
                </aside>
            @endif
        </header>

        @if ($post->cover_image)
            <figure class="mx-auto mt-10 max-w-5xl px-4 sm:px-6">
                <img src="{{ $post->coverUrl() }}"
                     srcset="{{ $post->coverUrl(480) }} 480w, {{ $post->coverUrl(960) }} 960w, {{ $post->coverUrl() }} 1600w"
                     sizes="(min-width: 1024px) 1024px, 100vw"
                     width="1600" height="900" fetchpriority="high" alt="{{ $post->cover_alt }}"
                     class="aspect-video w-full rounded-xl object-cover">
            </figure>
        @endif

        <div class="mx-auto mt-10 grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_16rem] lg:gap-14">
            <div class="mx-auto w-full max-w-3xl lg:mx-0">
                @if ($body->hasTableOfContents())
                    <details class="mb-8 rounded-xl border border-line bg-surface lg:hidden">
                        <summary class="cursor-pointer px-5 py-3 font-semibold text-ink">In this article</summary>
                        <x-toc :headings="$body->headings" class="px-5 pb-4" />
                    </details>
                @endif

                <div class="prose prose-lg max-w-none prose-headings:scroll-mt-24">
                    {{ $body->beforeBreak }}
                </div>

                @if (filled($midCtaText))
                    <aside class="my-10 rounded-xl bg-secondary-light p-6 sm:flex sm:items-center sm:justify-between sm:gap-6">
                        <p class="font-medium text-ink">{{ $midCtaText }}</p>
                        @if (filled($midCtaLabel) && filled($midCtaLink))
                            <a href="{{ NavLink::href($midCtaLink) }}" class="mt-4 inline-flex shrink-0 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white no-underline hover:bg-primary-dark sm:mt-0">
                                {{ $midCtaLabel }}
                            </a>
                        @endif
                    </aside>
                @endif

                <div class="prose prose-lg max-w-none prose-headings:scroll-mt-24">
                    {{ $body->afterBreak }}
                </div>

                @if ($post->key_takeaway)
                    <aside aria-label="Key takeaway" class="mt-10 rounded-xl border border-accent bg-accent/15 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink">Key takeaway</p>
                        <p class="mt-1 text-lg font-medium text-ink">{{ $post->key_takeaway }}</p>
                    </aside>
                @endif

                @if ($post->faqs)
                    <section aria-labelledby="faqs" class="mt-12">
                        <h2 id="faqs" class="text-2xl">Frequently asked questions</h2>
                        <div class="mt-4 divide-y divide-line rounded-xl border border-line">
                            @foreach ($post->faqs as $faq)
                                <details class="group px-5 py-4" @if ($loop->first) open @endif>
                                    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 font-semibold text-ink">
                                        <span>{{ $faq['question'] }}</span>
                                        <span aria-hidden="true" class="mt-0.5 text-primary transition-transform group-open:rotate-45">+</span>
                                    </summary>
                                    <p class="mt-2 text-muted">{{ $faq['answer'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($post->sources)
                    <section aria-labelledby="sources" class="mt-12">
                        <h2 id="sources" class="text-xl">Sources</h2>
                        <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm text-muted marker:text-muted">
                            @foreach ($post->sources as $source)
                                <li><a href="{{ $source['url'] }}" rel="noopener" target="_blank">{{ $source['title'] }}</a></li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if ($post->tags->isNotEmpty())
                    <ul class="mt-10 flex flex-wrap gap-2" aria-label="Tags">
                        @foreach ($post->tags as $tag)
                            <li class="rounded-full bg-surface px-3 py-1 text-xs font-medium text-muted">{{ $tag->name }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($jobCategories->isNotEmpty())
                    <aside aria-label="Related jobs" class="mt-12 rounded-xl bg-primary-light p-6">
                        <p class="font-semibold text-ink">Ready to put this into practice?</p>
                        <p class="mt-1 text-sm text-muted">Browse hand-picked jobs, each checked by a real person.</p>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($jobCategories as $jobCategory)
                                <li>
                                    <a href="{{ $jobCategory->url() }}" class="inline-flex rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-primary no-underline hover:bg-primary hover:text-white">{{ $jobCategory->name }} jobs</a>
                                </li>
                            @endforeach
                        </ul>
                    </aside>
                @endif

                <aside aria-label="About the author" class="mt-12 flex gap-4 rounded-xl border border-line p-6">
                    <x-avatar :user="$post->author" :size="64" />
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted">Written by</p>
                        <p class="mt-1 text-lg font-semibold text-ink">
                            @if ($authorHasPage)
                                <a href="{{ $post->author->authorUrl() }}" class="text-ink no-underline hover:text-primary">{{ $post->author->name }}</a>
                            @else
                                {{ $post->author->name }}
                            @endif
                        </p>
                        @if ($post->author->headline)
                            <p class="text-sm text-muted">{{ $post->author->headline }}</p>
                        @endif
                        @if ($post->author->bio)
                            <p class="mt-2 text-sm text-ink">{{ $post->author->bio }}</p>
                        @endif
                    </div>
                </aside>

                @if ($isLive && filled(setting('article.newsletter_text')))
                    <aside aria-labelledby="article-newsletter" class="mt-12 rounded-2xl bg-primary px-6 py-8 text-white sm:px-8">
                        <h2 id="article-newsletter" class="text-xl text-white">Get the next one by email</h2>
                        <p class="mt-2 text-white/85">{{ setting('article.newsletter_text') }}</p>
                        <x-newsletter-form source="article" :dark="true" class="mt-5" />
                    </aside>
                @endif
            </div>

            @if ($body->hasTableOfContents())
                <aside class="hidden lg:block">
                    <nav aria-label="In this article" class="sticky top-24">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted">In this article</p>
                        <x-toc :headings="$body->headings" class="mt-3" />
                    </nav>
                </aside>
            @endif
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section aria-labelledby="related" class="mt-16 border-t border-line bg-surface">
            <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
                <div class="flex items-end justify-between gap-4">
                    <h2 id="related" class="text-2xl">More in {{ $post->category->name }}</h2>
                    <a href="{{ $post->category->url() }}" class="text-sm font-semibold">See all</a>
                </div>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $relatedPost)
                        <x-article-card :post="$relatedPost" :show-category="false" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
