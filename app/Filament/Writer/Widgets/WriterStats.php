<?php

declare(strict_types=1);

namespace App\Filament\Writer\Widgets;

use App\Enums\PostStatus;
use App\Filament\Writer\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The writer's dashboard at a glance: what's in progress, with the editor, and live. */
class WriterStats extends StatsOverviewWidget
{
    // Three small counts: render with the page instead of popping in afterwards.
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $counts = Post::where('author_id', auth()->id())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (PostStatus ...$statuses): int => collect($statuses)->sum(fn (PostStatus $status) => (int) ($counts[$status->value] ?? 0));
        $listUrl = PostResource::getUrl();

        return [
            Stat::make('In progress', $count(PostStatus::Draft, PostStatus::ChangesRequested))
                ->description($count(PostStatus::ChangesRequested) > 0 ? 'Changes requested on '.$count(PostStatus::ChangesRequested) : 'Drafts you\'re working on')
                ->color($count(PostStatus::ChangesRequested) > 0 ? 'info' : 'gray')
                ->url($listUrl),
            Stat::make('With the editor', $count(PostStatus::Pending))
                ->description('Awaiting review')
                ->color('warning')
                ->url($listUrl),
            Stat::make('Published', $count(PostStatus::Published))
                ->description('Live on the site')
                ->color('success')
                ->url($listUrl),
        ];
    }
}
