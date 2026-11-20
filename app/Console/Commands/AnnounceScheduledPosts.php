<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use App\Support\IndexNow;
use App\Support\LlmsTxt;
use App\Support\PageCache;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * An article scheduled for later goes live without anyone saving it, so nothing would refresh the homepage cache
 * or tell search engines. This catches articles whose publish time has just passed.
 */
class AnnounceScheduledPosts extends Command
{
    private const LAST_RUN_KEY = 'posts:announce-scheduled:last-run';

    protected $signature = 'posts:announce-scheduled';

    protected $description = 'Refresh caches and notify search engines for scheduled articles that just went live';

    public function handle(IndexNow $indexNow): int
    {
        $since = Carbon::parse(Cache::get(self::LAST_RUN_KEY, now()->subMinutes(10)->toIso8601String()));
        Cache::forever(self::LAST_RUN_KEY, now()->toIso8601String());

        $goneLive = Post::published()
            ->with('category')
            ->where('published_at', '>', $since)
            ->whereColumn('published_at', '>', 'updated_at')
            ->get();

        if ($goneLive->isEmpty()) {
            return self::SUCCESS;
        }

        PageCache::flush();
        LlmsTxt::forget();
        $goneLive->each(fn (Post $post) => $indexNow->submit($post->url()));

        $this->info("Announced {$goneLive->count()} scheduled article(s).");

        return self::SUCCESS;
    }
}
