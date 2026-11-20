<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkMode;
use App\Support\ImageProcessor;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** A job listing, added by hand by the owner, linking to the employer's real page. */
#[Fillable([
    'title', 'slug', 'company_name', 'company_url', 'company_logo', 'job_category_id', 'region_id', 'country_code',
    'location_note', 'work_mode', 'employment_type', 'experience_level', 'summary', 'requirements', 'salary_text',
    'apply_url', 'source_name', 'source_url', 'posted_at', 'last_verified_at', 'closes_at', 'status', 'closed_at',
    'is_featured', 'seo_title', 'meta_description',
])]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    /** Open jobs not checked for this many days are flagged for the owner to re-check. */
    public const RECHECK_AFTER_DAYS = 30;

    protected $table = 'job_listings';

    protected static function booted(): void
    {
        static::creating(function (Job $job) {
            $job->slug = filled($job->slug) ? $job->slug : static::uniqueSlugFor("{$job->title} {$job->company_name}");
            $job->posted_at ??= today();
            $job->last_verified_at ??= now();
            $job->status ??= JobStatus::Open;
        });

        static::saving(function (Job $job) {
            // Keep closed_at in step with the status, whichever way it was changed.
            if ($job->isDirty('status')) {
                $job->closed_at = $job->status === JobStatus::Closed ? ($job->closed_at ?? now()) : null;
            }
        });

        static::updated(function (Job $job) {
            if ($job->wasChanged(['slug', 'job_category_id'])) {
                $oldCategorySlug = JobCategory::whereKey($job->getOriginal('job_category_id'))->value('slug');
                $job->unsetRelation('jobCategory');

                Redirect::recordMove("/jobs/{$oldCategorySlug}/{$job->getOriginal('slug')}", $job->path());
            }

            if ($job->wasChanged('company_logo')) {
                app(ImageProcessor::class)->delete($job->getOriginal('company_logo'));
            }
        });

        static::deleted(function (Job $job) {
            app(ImageProcessor::class)->delete($job->company_logo);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'work_mode' => WorkMode::class,
            'employment_type' => EmploymentType::class,
            'experience_level' => ExperienceLevel::class,
            'posted_at' => 'date',
            'closes_at' => 'date',
            'last_verified_at' => 'datetime',
            'closed_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public static function uniqueSlugFor(string $text): string
    {
        $base = Str::limit(Str::slug($text), 80, '') ?: 'job';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<JobCategory, $this>
     */
    public function jobCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * @param  Builder<Job>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', JobStatus::Open);
    }

    /**
     * Open jobs nobody has checked recently.
     *
     * @param  Builder<Job>  $query
     */
    public function scopeNeedsRecheck(Builder $query): void
    {
        $query->open()->where(fn (Builder $query) => $query
            ->whereNull('last_verified_at')
            ->orWhere('last_verified_at', '<', now()->subDays(self::RECHECK_AFTER_DAYS)));
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Open;
    }

    public function isDueForRecheck(): bool
    {
        return $this->isOpen() && ($this->last_verified_at === null || $this->last_verified_at->lt(now()->subDays(self::RECHECK_AFTER_DAYS)));
    }

    public function close(): void
    {
        $this->update(['status' => JobStatus::Closed]);
    }

    public function reopen(): void
    {
        $this->update(['status' => JobStatus::Open]);
    }

    public function markVerified(): void
    {
        $this->update(['last_verified_at' => now()]);
    }

    /**
     * A fresh, open copy for a similar role, ready to edit.
     */
    public function duplicate(): self
    {
        $copy = $this->replicate(['slug', 'posted_at', 'last_verified_at', 'closed_at', 'status']);
        $copy->title = $this->title.' (copy)';
        $copy->status = JobStatus::Open;
        $copy->company_logo = app(ImageProcessor::class)->copy($this->company_logo);
        $copy->save();

        return $copy;
    }

    public function path(): string
    {
        return '/jobs/'.$this->jobCategory->slug.'/'.$this->slug;
    }

    public function url(): string
    {
        return url($this->path());
    }

    public function logoUrl(): ?string
    {
        return media_url($this->company_logo);
    }

    /**
     * @return list<string>
     */
    public function requirementsList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->requirements))));
    }

    public function countryName(): ?string
    {
        return $this->country_code ? config('countries.'.$this->country_code) : null;
    }
}
