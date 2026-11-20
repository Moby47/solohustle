<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WorkMode;
use App\Models\JobCategory;
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
