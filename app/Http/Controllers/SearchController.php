<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Job;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Site search across open jobs and published articles. */
class SearchController extends Controller
{
    private const JOBS_SHOWN = 5;

    private const ARTICLES_PER_PAGE = 9;

    public function __invoke(Request $request): View
    {
        $query = Str::limit(trim((string) $request->query('q', '')), 100, '');

        if ($query === '') {
            return view('search', ['query' => '', 'jobs' => collect(), 'posts' => null, 'categories' => $this->categories()]);
        }

        $jobs = Job::search($query)
            ->query(fn (Builder $builder) => $builder->open()->with(['jobCategory', 'region']))
            ->take(self::JOBS_SHOWN)
            ->get();

        $posts = Post::search($query)
            ->query(fn (Builder $builder) => $builder->published()->with(['category', 'author']))
            ->paginate(self::ARTICLES_PER_PAGE)
            ->withQueryString();

        return view('search', [
            'query' => $query,
            'jobs' => $jobs,
            'posts' => $posts,
            'categories' => $this->categories(),
        ]);
    }

    /**
     * Suggestions when there's nothing to show yet.
     */
    private function categories()
    {
        return Category::orderBy('sort')->get(['name', 'slug']);
    }
}
