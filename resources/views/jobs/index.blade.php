<x-layouts.app :title="$metaTitle" :description="$metaDescription">
    <section class="border-b border-line bg-surface">
        <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
            <x-breadcrumb :items="$breadcrumb" />
            <h1 class="mt-4 text-3xl sm:text-5xl">{{ $heading }}</h1>
            @if (filled($intro))
                <p class="mt-4 max-w-2xl text-lg text-muted">{{ $intro }}</p>
            @endif

            @if ($jobCategories->isNotEmpty())
                <nav aria-label="Browse jobs by category" class="mt-6">
                    <ul class="flex flex-wrap gap-2">
                        <li><a href="{{ url('/jobs/remote') }}" class="inline-flex rounded-full bg-primary px-3 py-1.5 text-sm font-medium text-white no-underline hover:bg-primary-dark">Remote jobs</a></li>
                        @foreach ($jobCategories as $jobCategory)
                            <li>
                                <a href="{{ $jobCategory->url() }}" class="inline-flex rounded-full border border-line bg-white px-3 py-1.5 text-sm font-medium text-ink no-underline hover:border-primary hover:text-primary">{{ $jobCategory->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </section>

    <livewire:job-directory :base-path="$basePath" :locked-category-id="$lockedCategoryId" :locked-work-mode="$lockedWorkMode" />
</x-layouts.app>
