<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Top-level /{slug}: a blog category or an owner page (they never share an address). */
class ShowPageOrCategory extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $category = Category::where('slug', $slug)->first();

        if ($category !== null) {
            return $this->category($category);
        }

        return $this->page($request, $slug);
    }

    private function category(Category $category): View
    {
        $posts = $category->posts()
            ->published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(12);

        // A page number past the end (old link or a bot) is "not found", not an empty category.
        abort_if($posts->isEmpty() && $posts->currentPage() > 1, 404);

        return view('categories.show', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }

    private function page(Request $request, string $slug): View
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $isAdmin = (bool) $request->user()?->isAdmin();

        abort_if(! $page->is_published && ! $isAdmin, 404);

        return view('pages.show', [
            'page' => $page,
            'isAdmin' => $isAdmin,
        ]);
    }
}
