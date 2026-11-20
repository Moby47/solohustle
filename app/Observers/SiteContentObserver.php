<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\PostStatus;
use App\Models\Job;
use App\Models\Post;
use App\Support\IndexNow;
use App\Support\LlmsTxt;
use App\Support\PageCache;
use Illuminate\Database\Eloquent\Model;

/** Keeps everything derived from public content fresh when that content changes. */
class SiteContentObserver
{
    /** Changes that don't alter what visitors see, so search engines needn't hear about them. */
    private const INVISIBLE_CHANGES = ['updated_at', 'last_verified_at', 'brevo_synced_at', 'reading_minutes'];

    public function created(Model $model): void
    {
        LlmsTxt::forget();
        PageCache::flush();

        $this->notifySearchEngines($model);
    }

    public function updated(Model $model): void
    {
        LlmsTxt::forget();
        PageCache::flush();

        if (array_diff(array_keys($model->getChanges()), self::INVISIBLE_CHANGES) !== []) {
            $this->notifySearchEngines($model);
        }
    }

    public function deleted(Model $model): void
    {
        LlmsTxt::forget();
        PageCache::flush();
    }

    /**
     * IndexNow wants to hear about new, changed and removed public pages, not drafts.
     */
    private function notifySearchEngines(Model $model): void
    {
        $url = match (true) {
            $model instanceof Post && $this->postIsOrWasPublic($model) => $model->url(),
            $model instanceof Job => $model->url(),
            default => null,
        };

        if ($url !== null) {
            app(IndexNow::class)->submit($url);
        }
    }

    /**
     * Scheduled articles aren't public yet; AnnounceScheduledPosts tells search engines when their time comes.
     */
    private function postIsOrWasPublic(Post $post): bool
    {
        $isLiveNow = $post->status === PostStatus::Published && $post->published_at?->isPast();

        return $isLiveNow || $post->getOriginal('status') === PostStatus::Published;
    }
}
