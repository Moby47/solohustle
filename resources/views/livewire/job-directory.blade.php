@php
    use App\Enums\EmploymentType;
    use App\Enums\ExperienceLevel;
    use App\Enums\WorkMode;

    $jobs = $this->jobs;
    $selectClass = 'mt-1 block w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none';
    $labelClass = 'block text-sm font-medium text-ink';
@endphp

<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <form method="GET" action="{{ url($basePath) }}" wire:submit="search" role="search" aria-label="Filter jobs"
          class="lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10">
        <div>
            <label for="job-q" class="sr-only">Search jobs</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input id="job-q" type="search" name="q" value="{{ $q }}" wire:model.live.debounce.400ms="q"
                       placeholder="Job title, company or keyword"
                       class="block w-full rounded-md border border-line bg-white py-2.5 pl-10 pr-3 text-ink placeholder:text-muted focus:border-primary focus:outline-none">
            </div>

            {{-- Collapsible on phones, always open on desktop; without JavaScript the filters simply stay visible. --}}
            <div x-data="{ open: @js($this->hasActiveFilters()) }" class="mt-4 rounded-xl border border-line lg:border-0">
                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-controls="job-filters"
                        class="flex w-full items-center justify-between px-4 py-3 font-semibold text-ink lg:hidden">
                    Filters
                    <span aria-hidden="true" class="text-primary transition-transform" x-bind:class="open && 'rotate-45'">+</span>
                </button>

                <div id="job-filters" class="space-y-4 px-4 pb-4 pt-4 lg:px-0 lg:pt-0" x-bind:class="open ? '' : 'hidden lg:block'">
                    @if ($categories->isNotEmpty())
                        <div>
                            <label for="job-category" class="{{ $labelClass }}">Category</label>
                            <select id="job-category" name="category" wire:model.live="category" class="{{ $selectClass }}">
                                <option value="">All categories</option>
                                @foreach ($categories as $option)
                                    <option value="{{ $option->slug }}" @selected($category === $option->slug)>{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @unless ($lockedWorkMode)
                        <div>
                            <label for="job-mode" class="{{ $labelClass }}">Work mode</label>
                            <select id="job-mode" name="mode" wire:model.live="mode" class="{{ $selectClass }}">
                                <option value="">Any</option>
                                @foreach (WorkMode::cases() as $case)
                                    <option value="{{ $case->value }}" @selected($mode === $case->value)>{{ $case->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endunless

                    <div>
                        <label for="job-region" class="{{ $labelClass }}">Region</label>
                        <select id="job-region" name="region" wire:model.live="region" class="{{ $selectClass }}">
                            <option value="">Anywhere</option>
                            @foreach ($regions as $option)
                                <option value="{{ $option->slug }}" @selected($region === $option->slug)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="job-type" class="{{ $labelClass }}">Job type</label>
                        <select id="job-type" name="type" wire:model.live="type" class="{{ $selectClass }}">
                            <option value="">Any</option>
                            @foreach (EmploymentType::cases() as $case)
                                <option value="{{ $case->value }}" @selected($type === $case->value)>{{ $case->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="job-level" class="{{ $labelClass }}">Experience</label>
                        <select id="job-level" name="level" wire:model.live="level" class="{{ $selectClass }}">
                            <option value="">Any</option>
                            @foreach (ExperienceLevel::cases() as $case)
                                <option value="{{ $case->value }}" @selected($level === $case->value)>{{ $case->getLabel() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="job-posted" class="{{ $labelClass }}">Date posted</label>
                        <select id="job-posted" name="posted" wire:model.live="posted" class="{{ $selectClass }}">
                            <option value="">Any time</option>
                            <option value="1" @selected($posted === '1')>Last 24 hours</option>
                            <option value="7" @selected($posted === '7')>Last 7 days</option>
                            <option value="30" @selected($posted === '30')>Last 30 days</option>
                        </select>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="closed" value="1" wire:model.live="closed" @checked($closed) class="size-4 rounded border-line text-primary">
                        Include closed jobs
                    </label>

                    <noscript>
                        <button type="submit" class="w-full rounded-md bg-primary px-4 py-2 font-semibold text-white">Show jobs</button>
                    </noscript>
                </div>
            </div>
        </div>

        <section aria-live="polite" class="mt-8 lg:mt-0">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted">
                    <strong class="text-ink">{{ number_format($jobs->total()) }}</strong> {{ str('job')->plural($jobs->total()) }}
                    <span wire:loading class="ml-2 text-primary">Updating…</span>
                </p>
                @if ($this->hasActiveFilters())
                    <a href="{{ url($basePath) }}" wire:click.prevent="clearFilters" class="text-sm font-semibold">Clear filters</a>
                @endif
            </div>

            @if ($jobs->isEmpty())
                <div class="mt-6 rounded-xl border border-dashed border-line px-6 py-14 text-center">
                    <h2 class="text-xl">No jobs match these filters</h2>
                    <p class="mt-2 text-muted">Try fewer filters or a different keyword. New jobs are added regularly.</p>
                    @if ($this->hasActiveFilters())
                        <a href="{{ url($basePath) }}" wire:click.prevent="clearFilters" class="mt-6 inline-flex rounded-md bg-primary px-5 py-2.5 font-semibold text-white no-underline hover:bg-primary-dark">Clear filters</a>
                    @endif
                </div>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($jobs as $job)
                        <li wire:key="job-{{ $job->id }}"><x-job-card :job="$job" /></li>
                    @endforeach
                </ul>

                <div class="mt-8">
                    {{ $jobs->links('pagination.site', ['livewire' => true]) }}
                </div>
            @endif
        </section>
    </form>
</div>
