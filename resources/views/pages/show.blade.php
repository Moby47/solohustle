<x-layouts.app :title="$page->seo_title ?: $page->title" :description="$page->meta_description" :noindex="! $page->is_published">
    @if ($isAdmin)
        <x-admin-bar :highlight="! $page->is_published"
                     :edit-url="\App\Filament\Resources\Pages\PageResource::getUrl('edit', ['record' => $page], panel: 'admin')">
            @if ($page->is_published)
                You're signed in as the owner.
            @else
                <strong class="font-semibold">Draft preview.</strong> Only you can see this. Publish it in the dashboard to make it public.
            @endif
        </x-admin-bar>
    @endif

    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-20">
        <h1 class="text-3xl leading-tight sm:text-5xl">{{ $page->title }}</h1>

        <div class="prose prose-lg mt-8 max-w-none sm:mt-10">
            {{ $page->renderedBody() }}
        </div>

        {{-- The contact form always lives on the page with the "contact" address. --}}
        @if ($page->slug === 'contact')
            <x-contact-form />
        @endif
    </article>
</x-layouts.app>
