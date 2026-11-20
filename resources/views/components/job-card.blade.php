@props(['job'])

<article @class([
    'group relative flex gap-4 rounded-xl border bg-white p-4 transition-shadow hover:shadow-md sm:p-5',
    'border-accent' => $job->is_featured && $job->isOpen(),
    'border-line' => ! ($job->is_featured && $job->isOpen()),
    'opacity-75' => ! $job->isOpen(),
])>
    @if ($job->logoUrl())
        <img src="{{ $job->logoUrl() }}" alt="" width="48" height="48" loading="lazy" class="size-12 shrink-0 rounded-lg border border-line bg-white object-contain p-1">
    @else
        <span aria-hidden="true" class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-primary-light text-lg font-bold text-primary">
            {{ mb_substr($job->company_name, 0, 1) }}
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            @if ($job->is_featured && $job->isOpen())
                <span class="rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-ink">Featured</span>
            @endif
            @unless ($job->isOpen())
                <span class="rounded-full bg-surface px-2 py-0.5 text-xs font-semibold text-muted">Closed</span>
            @endunless
        </div>
        <h3 class="mt-1 text-base leading-snug sm:text-lg">
            <a href="{{ $job->url() }}" class="text-ink no-underline after:absolute after:inset-0 group-hover:text-primary">{{ $job->title }}</a>
        </h3>
        <p class="text-sm text-muted">{{ $job->company_name }}</p>

        <ul class="mt-3 flex flex-wrap gap-1.5 text-xs" aria-label="Job details">
            <li class="rounded-full bg-primary-light px-2.5 py-1 font-medium text-primary">{{ $job->work_mode->getLabel() }}</li>
            <li class="rounded-full bg-surface px-2.5 py-1 text-ink">{{ $job->employment_type->getLabel() }}</li>
            <li class="rounded-full bg-surface px-2.5 py-1 text-ink">{{ $job->region->name }}</li>
            @if ($job->salary_text)
                <li class="rounded-full bg-surface px-2.5 py-1 text-ink">{{ $job->salary_text }}</li>
            @endif
        </ul>
    </div>

    <p class="hidden shrink-0 text-xs text-muted sm:block">
        <time datetime="{{ $job->posted_at->toDateString() }}">{{ $job->posted_at->isToday() ? 'Today' : $job->posted_at->diffForHumans(['parts' => 1]) }}</time>
    </p>
</article>
