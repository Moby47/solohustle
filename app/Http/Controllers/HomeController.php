<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/** The homepage. Every section, its title and how much it shows come from Site settings → Homepage. */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sections = setting('homepage');

        $featured = $this->when($sections['guides']['visible'], fn () => Post::published()
            ->with(['category', 'author'])
            ->where('is_featured', true)
            ->latest('published_at')
            ->limit(3)
            ->get());

        $latestPosts = $this->when($sections['articles']['visible'], fn () => Post::published()
            ->with(['category', 'author'])
            ->whereNotIn('id', $featured->pluck('id'))
            ->latest('published_at')
            ->limit((int) $sections['articles']['count'])
            ->get());

        $jobs = $this->when($sections['jobs']['visible'], fn () => Job::open()
            ->with(['jobCategory', 'region'])
            ->orderByDesc('is_featured')
            ->latest('posted_at')
            ->limit((int) $sections['jobs']['count'])
            ->get());

        return view('home', [
            'sections' => $sections,
            'jobs' => $jobs,
            'jobCategories' => $jobs->isNotEmpty() ? JobCategory::orderBy('sort')->get(['name', 'slug']) : collect(),
            'featured' => $featured,
            'latestPosts' => $latestPosts,
        ]);
    }

    /**
     * Only query for sections the owner has switched on.
     */
    private function when(bool $visible, callable $query): Collection
    {
        return $visible ? $query() : collect();
    }
}
