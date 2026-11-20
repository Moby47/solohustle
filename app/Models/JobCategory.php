<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\JobCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A kind of job (Content Writing, SEO, …), with its own landing page at /jobs/{slug}. */
#[Fillable(['name', 'slug', 'intro', 'seo_title', 'meta_description', 'sort', 'related_category_id'])]
class JobCategory extends Model
{
    /** @use HasFactory<JobCategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Renaming moves the landing page and every job in it, so keep old links working.
        static::updated(function (JobCategory $jobCategory) {
            if (! $jobCategory->wasChanged('slug')) {
                return;
            }

            $old = $jobCategory->getOriginal('slug');

            Redirect::recordMove("/jobs/{$old}", "/jobs/{$jobCategory->slug}");

            $jobCategory->jobs()->pluck('slug')
                ->each(fn (string $slug) => Redirect::recordMove("/jobs/{$old}/{$slug}", "/jobs/{$jobCategory->slug}/{$slug}"));
        });
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * The blog category whose guides suit people looking at these jobs.
     *
     * @return BelongsTo<Category, $this>
     */
    public function relatedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'related_category_id');
    }

    public function url(): string
    {
        return url('/jobs/'.$this->slug);
    }
}
