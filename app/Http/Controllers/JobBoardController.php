<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WorkMode;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Post;
use App\Support\JobPostingSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The public job pages: all jobs, remote jobs, and one landing page per job category. */
class JobBoardController extends Controller
{
    public function index(): View
    {
        return view('jobs.index', [
            'heading' => setting('jobs.headline'),
            'intro' => setting('jobs.intro'),
            'metaTitle' => setting('jobs.headline'),
            'metaDescription' => setting('jobs.meta_description'),
            'breadcrumb' => ['Jobs' => null],
            'basePath' => '/jobs',
            'lockedCategoryId' => null,
            'lockedWorkMode' => null,
            'jobCategories' => JobCategory::orderBy('sort')->get(),
        ]);
    }

    public function remote(): View
    {
        return view('jobs.index', [
            'heading' => setting('jobs.remote_headline'),
            'intro' => setting('jobs.remote_intro'),
            'metaTitle' => setting('jobs.remote_headline'),
            'metaDescription' => setting('jobs.remote_meta_description'),
            'breadcrumb' => ['Jobs' => url('/jobs'), 'Remote' => null],
            'basePath' => '/jobs/remote',
            'lockedCategoryId' => null,
            'lockedWorkMode' => WorkMode::Remote->value,
            'jobCategories' => collect(),
        ]);
    }

    public function show(Request $request, string $categorySlug, string $jobSlug): View|RedirectResponse
    {
        $job = Job::with(['jobCategory.relatedCategory', 'region'])->where('slug', $jobSlug)->firstOrFail();

        // The job moved category: send visitors to its current address.
        if ($job->jobCategory->slug !== $categorySlug) {
            return redirect($job->url(), 301);
        }

        $relatedJobs = Job::open()
            ->with(['jobCategory', 'region'])
            ->where('job_category_id', $job->job_category_id)
            ->whereKeyNot($job->getKey())
            ->orderByDesc('is_featured')
            ->latest('posted_at')
            ->limit(3)
            ->get();

        $relatedGuides = $job->jobCategory->relatedCategory
            ? Post::published()->with(['category', 'author'])
                ->where('category_id', $job->jobCategory->related_category_id)
                ->latest('published_at')
                ->limit(3)
                ->get()
            : collect();

        return view('jobs.show', [
            'job' => $job,
            'relatedJobs' => $relatedJobs,
            'relatedGuides' => $relatedGuides,
            'schema' => JobPostingSchema::for($job),
            'isAdmin' => (bool) $request->user()?->isAdmin(),
        ]);
    }

    public function category(string $slug): View
    {
        $jobCategory = JobCategory::where('slug', $slug)->firstOrFail();

        return view('jobs.index', [
            'heading' => "{$jobCategory->name} jobs",
            'intro' => $jobCategory->intro,
            'metaTitle' => $jobCategory->seo_title ?: "{$jobCategory->name} jobs",
            'metaDescription' => $jobCategory->meta_description ?: $jobCategory->intro,
            'breadcrumb' => ['Jobs' => url('/jobs'), $jobCategory->name => null],
            'basePath' => '/jobs/'.$jobCategory->slug,
            'lockedCategoryId' => $jobCategory->id,
            'lockedWorkMode' => null,
            'jobCategories' => collect(),
        ]);
    }
}
