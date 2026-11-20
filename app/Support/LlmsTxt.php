<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use App\Models\JobCategory;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

/**
 * /llms.txt: a short, plain guide to the site for AI assistants (see llmstxt.org).
 * Cached, and cleared whenever articles, pages, categories or settings change.
 */
final class LlmsTxt
{
    public const CACHE_KEY = 'llms.txt';

    private const RECENT_ARTICLES = 20;

    /** Pages worth pointing an assistant to first, if they exist. */
    private const KEY_PAGES = ['about', 'editorial-policy', 'creator-guidelines', 'contact'];

    public function content(): string
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): string => $this->build());
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): string
    {
        $lines = [
            '# '.setting('general.site_name'),
            '',
            '> '.setting('seo.default_meta_description'),
            '',
            'Hand-picked remote and online jobs (each linking to the employer\'s own page), practical guides, and articles by guest writers reviewed by an editor.',
            '',
            '## Key pages',
            '',
            ...$this->keyPages(),
            '- ['.'Publish with us'.']('.url('/publish').'): how guest writers can submit articles',
            '',
            '## Jobs',
            '',
            '- [All jobs]('.url('/jobs').'): every open job, filterable by category, region, type and experience',
            '- [Remote jobs]('.url('/jobs/remote').')',
            ...JobCategory::orderBy('sort')->get()->map(fn (JobCategory $jobCategory) => "- [{$jobCategory->name} jobs]({$jobCategory->url()})"),
            '',
            '## Guides by topic',
            '',
            ...Category::orderBy('sort')->get()->map(fn (Category $category) => "- [{$category->name}]({$category->url()})".($category->description ? ": {$category->description}" : '')),
            '',
            '## Recent articles',
            '',
            ...Post::published()->with('category')->latest('published_at')->limit(self::RECENT_ARTICLES)->get()
                ->map(fn (Post $post) => "- [{$post->title}]({$post->url()})".($post->excerpt ? ": {$post->excerpt}" : '')),
        ];

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<string>
     */
    private function keyPages(): array
    {
        return Page::published()->whereIn('slug', self::KEY_PAGES)->get()
            ->sortBy(fn (Page $page) => array_search($page->slug, self::KEY_PAGES, true))
            ->map(fn (Page $page) => "- [{$page->title}]({$page->url()})")
            ->values()
            ->all();
    }
}
