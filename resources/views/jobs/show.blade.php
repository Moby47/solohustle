@php
    $isOpen = $job->isOpen();
    $where = collect([$job->region->name, $job->location_note, $job->countryName()])->filter()->unique()->implode(' · ');
    $paragraphs = collect(preg_split('/\R{2,}/', trim($job->summary)))->filter();
@endphp

<x-layouts.app :title="$job->seo_title ?: $job->title.' at '.$job->company_name"
               :description="$job->meta_description ?: \Illuminate\Support\Str::limit($job->summary, 155)"
               :noindex="! $isOpen">
    @if ($schema)
        <x-json-ld :data="$schema" />
    @endif

    @if ($isAdmin)
        <x-admin-bar :edit-url="\App\Filament\Resources\Jobs\JobResource::getUrl('edit', ['record' => $job], panel: 'admin')">
            You're signed in as the owner. Last checked {{ $job->last_verified_at?->diffForHumans() ?? 'never' }}.
        </x-admin-bar>
    @endif

    @unless ($isOpen)
        <div class="border-b border-line bg-surface">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
                <p class="text-ink"><strong class="font-semibold">This job is closed.</strong> It's no longer accepting applications.</p>
                <a href="{{ $job->jobCategory->url() }}" class="text-sm font-semibold">See open {{ $job->jobCategory->name }} jobs</a>
            </div>
        </div>
    @endunless

    <article class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <x-breadcrumb :items="['Jobs' => url('/jobs'), $job->jobCategory->name => $job->jobCategory->url(), $job->title => null]" />

        <header class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex gap-4">
                @if ($job->logoUrl())
                    <img src="{{ $job->logoUrl() }}" alt="{{ $job->company_name }} logo" width="64" height="64" class="size-16 shrink-0 rounded-xl border border-line bg-white object-contain p-1.5">
                @else
                    <span aria-hidden="true" class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-primary-light text-2xl font-bold text-primary">{{ mb_substr($job->company_name, 0, 1) }}</span>
                @endif
                <div>
                    <h1 class="text-2xl leading-tight sm:text-4xl">{{ $job->title }}</h1>
                    <p class="mt-1 text-lg text-muted">
                        @if ($job->company_url)
                            <a href="{{ $job->company_url }}" rel="nofollow noopener" target="_blank" class="text-muted hover:text-primary">{{ $job->company_name }}</a>
                        @else
                            {{ $job->company_name }}
                        @endif
                    </p>
                    <ul class="mt-3 flex flex-wrap gap-1.5 text-sm" aria-label="Job details">
                        <li class="rounded-full bg-primary-light px-3 py-1 font-medium text-primary">{{ $job->work_mode->getLabel() }}</li>
                        <li class="rounded-full bg-surface px-3 py-1 text-ink">{{ $job->employment_type->getLabel() }}</li>
                        <li class="rounded-full bg-surface px-3 py-1 text-ink">{{ $job->region->name }}</li>
                        @if ($job->salary_text)
                            <li class="rounded-full bg-surface px-3 py-1 text-ink">{{ $job->salary_text }}</li>
                        @endif
                    </ul>
                </div>
            </div>

            @if ($isOpen)
                <div class="sm:text-right">
                    <a href="{{ $job->apply_url }}" rel="nofollow noopener" target="_blank"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-6 py-3 font-semibold text-white no-underline hover:bg-primary-dark sm:w-auto">
                        Apply now
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                    </a>
                    <p class="mt-2 text-xs text-muted">Opens {{ $job->company_name }}'s website in a new tab.</p>
                </div>
            @endif
        </header>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="max-w-3xl">
                <section aria-labelledby="about-role">
                    <h2 id="about-role" class="text-xl">About the role</h2>
                    <div class="prose mt-3 max-w-none">
                        @foreach ($paragraphs as $paragraph)
                            <p>{!! nl2br(e(trim($paragraph))) !!}</p>
                        @endforeach
                    </div>
                </section>

                @if ($job->requirementsList())
                    <section aria-labelledby="requirements" class="mt-10">
                        <h2 id="requirements" class="text-xl">What they're looking for</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($job->requirementsList() as $requirement)
                                <li class="flex gap-3 text-ink">
                                    <svg class="mt-1 size-4 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    <span>{{ $requirement }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            <aside aria-label="Job facts">
                <dl class="divide-y divide-line rounded-xl border border-line text-sm">
                    @foreach (array_filter([
                        'Category' => $job->jobCategory->name,
                        'Where' => $where,
                        'Work mode' => $job->work_mode->getLabel(),
                        'Type' => $job->employment_type->getLabel(),
                        'Experience' => $job->experience_level->getLabel(),
                        'Pay' => $job->salary_text,
                        'Posted' => $job->posted_at->format('j M Y'),
                        'Closes' => $job->closes_at?->format('j M Y'),
                    ]) as $label => $value)
                        <div class="flex justify-between gap-4 px-4 py-3">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="text-right font-medium text-ink">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="px-4 py-3">
                        <dt class="sr-only">Last checked</dt>
                        <dd class="flex items-center gap-2 text-muted">
                            <svg class="size-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Last checked {{ ($job->last_verified_at ?? $job->posted_at)->format('j M Y') }}
                        </dd>
                    </div>
                    @if ($job->source_name || $job->source_url)
                        <div class="px-4 py-3 text-muted">
                            <dt class="inline">Source:</dt>
                            <dd class="inline">
                                @if ($job->source_url)
                                    <a href="{{ $job->source_url }}" rel="nofollow noopener" target="_blank">{{ $job->source_name ?: 'Original listing' }}</a>
                                @else
                                    {{ $job->source_name }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
                <p class="mt-3 text-xs text-muted">
                    We check every listing by hand, but always apply on the employer's own site and never pay to apply for a job.
                </p>
            </aside>
        </div>
    </article>

    @if ($relatedJobs->isNotEmpty())
        <section aria-labelledby="similar-jobs" class="border-t border-line bg-surface">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <div class="flex items-end justify-between gap-4">
                    <h2 id="similar-jobs" class="text-2xl">More {{ $job->jobCategory->name }} jobs</h2>
                    <a href="{{ $job->jobCategory->url() }}" class="text-sm font-semibold">See all</a>
                </div>
                <ul class="mt-6 space-y-3">
                    @foreach ($relatedJobs as $relatedJob)
                        <li><x-job-card :job="$relatedJob" /></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($relatedGuides->isNotEmpty())
        <section aria-labelledby="guides" class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <div class="flex items-end justify-between gap-4">
                <h2 id="guides" class="text-2xl">Guides to help you land it</h2>
                <a href="{{ $job->jobCategory->relatedCategory->url() }}" class="text-sm font-semibold">More {{ $job->jobCategory->relatedCategory->name }} guides</a>
            </div>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedGuides as $guide)
                    <x-article-card :post="$guide" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
