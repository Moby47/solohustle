<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\JobCategory;
use App\Models\Post;
use App\Support\ArticleBody;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** An article at /{category}/{post}. Unpublished articles can be previewed by the owner and their author only. */
class ShowArticle extends Controller
{
    public function __invoke(Request $request, string $categorySlug, string $postSlug): View|RedirectResponse
    {
        $post = Post::with(['category', 'author', 'tags'])->where('slug', $postSlug)->firstOrFail();

        $isLive = $post->status === PostStatus::Published && $post->published_at?->isPast();

        abort_if(! $isLive && ! $request->user()?->can('view', $post), 404);

        // The article moved category: send visitors to its current address.
        if ($post->category->slug !== $categorySlug) {
            return redirect($post->url(), 301);
        }

        $related = Post::published()
            ->with(['category', 'author'])
            ->where('category_id', $post->category_id)
            ->whereKeyNot($post->getKey())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('articles.show', [
            'post' => $post,
            'body' => ArticleBody::from($post->renderedBody()->toHtml()),
            'related' => $related,
            'isLive' => $isLive,
            'isAdmin' => (bool) $request->user()?->isAdmin(),
            'authorHasPage' => $post->author->hasAuthorPage(),
            'jobCategories' => JobCategory::where('related_category_id', $post->category_id)->orderBy('sort')->get(),
        ]);
    }
}
