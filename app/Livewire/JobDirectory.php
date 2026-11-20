<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\WorkMode;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Region;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The public job list with filters. Filters live in the address bar, so every filtered view can be shared,
 * bookmarked and crawled, and the same address works without JavaScript.
 */
class JobDirectory extends Component
{
    use WithPagination;

    public const PER_PAGE = 20;

    /** @var array<string, int> "Date posted" choice => days back */
    public const POSTED_WITHIN = ['1' => 1, '7' => 7, '30' => 30];

    /** Set by landing pages (/jobs/remote, /jobs/{category}); never taken from the address bar. */
    #[Locked]
    public ?int $lockedCategoryId = null;

    #[Locked]
    public ?string $lockedWorkMode = null;

    #[Locked]
    public string $basePath = '/jobs';

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $mode = '';

    #[Url(except: '')]
    public string $region = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $level = '';

    #[Url(except: '')]
    public string $posted = '';

    #[Url(except: false)]
    public bool $closed = false;

    public function updated(string $property): void
    {
        if ($property !== 'paginators') {
            $this->resetPage();
        }
    }

    /**
     * The keyword box submits the form; results already update as you type, so there's nothing else to do.
     */
    public function search(): void {}

    public function clearFilters(): void
    {
        $this->reset(['q', 'category', 'mode', 'region', 'type', 'level', 'posted', 'closed']);
        $this->resetPage();
    }

    #[Computed]
    public function jobs(): LengthAwarePaginator
    {
        $jobs = $this->paginatedJobs();

        // An old link to a page that no longer exists (fewer jobs now): show page 1 instead of an empty list.
        if ($jobs->isEmpty() && $jobs->currentPage() > 1) {
            $this->resetPage();
            $jobs = $this->paginatedJobs();
        }

        return $jobs;
    }

    private function paginatedJobs(): LengthAwarePaginator
    {
        return $this->query()
            ->with(['jobCategory', 'region'])
            ->orderByDesc('is_featured')
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withPath(url($this->basePath))
            ->appends($this->activeFilters());
    }

    public function hasActiveFilters(): bool
    {
        return $this->activeFilters() !== [];
    }

    /**
     * Only filters that are set, as they appear in the address bar.
     *
     * @return array<string, string>
     */
    public function activeFilters(): array
    {
        return array_filter([
            'q' => trim($this->q),
            'category' => $this->category,
            'mode' => $this->mode,
            'region' => $this->region,
            'type' => $this->type,
            'level' => $this->level,
            'posted' => $this->posted,
            'closed' => $this->closed ? '1' : '',
        ], fn (string $value): bool => $value !== '');
    }

    public function render(): View
    {
        return view('livewire.job-directory', [
            'categories' => $this->lockedCategoryId ? collect() : JobCategory::orderBy('sort')->get(['name', 'slug']),
            'regions' => Region::orderBy('sort')->get(['name', 'slug']),
        ]);
    }

    /**
     * @return Builder<Job>
     */
    private function query(): Builder
    {
        $query = Job::query();

        if (! $this->closed) {
            $query->open();
        }

        if ($this->lockedCategoryId) {
            $query->where('job_category_id', $this->lockedCategoryId);
        } elseif ($this->category !== '') {
            $query->whereHas('jobCategory', fn (Builder $q) => $q->where('slug', $this->category));
        }

        $workMode = WorkMode::tryFrom((string) ($this->lockedWorkMode ?? $this->mode));
        if ($workMode) {
            $query->where('work_mode', $workMode);
        }

        if ($type = EmploymentType::tryFrom($this->type)) {
            $query->where('employment_type', $type);
        }

        if ($level = ExperienceLevel::tryFrom($this->level)) {
            $query->where('experience_level', $level);
        }

        if ($this->region !== '') {
            // Jobs open to applicants anywhere suit every region too.
            $query->whereHas('region', fn (Builder $q) => $q->where('slug', $this->region)->orWhere('is_worldwide', true));
        }

        if ($days = self::POSTED_WITHIN[$this->posted] ?? null) {
            $query->whereDate('posted_at', '>=', today()->subDays($days));
        }

        if (($keyword = mb_substr(trim($this->q), 0, 100)) !== '') {
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', "%{$keyword}%")
                ->orWhere('company_name', 'like', "%{$keyword}%")
                ->orWhere('summary', 'like', "%{$keyword}%"));
        }

        return $query;
    }
}
