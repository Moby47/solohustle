<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Jobs\JobResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\Post;
use App\Models\Subscriber;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** What needs the owner's attention today. Each box links straight to the right list. */
class OwnerStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|array|null $columns = ['default' => 1, 'sm' => 2, 'xl' => 4];

    protected function getStats(): array
    {
        $pending = Post::where('status', PostStatus::Pending)->count();
        $recheck = Job::needsRecheck()->count();
        $unread = ContactMessage::unread()->count();
        $newSubscribers = Subscriber::active()->where('confirmed_at', '>=', now()->subWeek())->count();

        return [
            Stat::make('Articles to review', $pending)
                ->description($pending > 0 ? 'Waiting for your decision' : 'All caught up')
                ->descriptionIcon($pending > 0 ? Heroicon::OutlinedClock : Heroicon::OutlinedCheckCircle)
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(PostResource::getUrl('index', ['tab' => 'pending'])),
            Stat::make('Jobs to re-check', $recheck)
                ->description($recheck > 0 ? 'Not checked for '.Job::RECHECK_AFTER_DAYS.'+ days' : 'All recently checked')
                ->descriptionIcon($recheck > 0 ? Heroicon::OutlinedExclamationTriangle : Heroicon::OutlinedCheckCircle)
                ->color($recheck > 0 ? 'warning' : 'success')
                ->url(JobResource::getUrl('index', ['tab' => 'recheck'])),
            Stat::make('Unread messages', $unread)
                ->description($unread > 0 ? 'In your inbox' : 'Inbox zero')
                ->descriptionIcon(Heroicon::OutlinedInbox)
                ->color($unread > 0 ? 'primary' : 'success')
                ->url(ContactMessageResource::getUrl('index', ['tab' => 'unread'])),
            Stat::make('New subscribers', $newSubscribers)
                ->description('Confirmed in the last 7 days')
                ->descriptionIcon(Heroicon::OutlinedEnvelope)
                ->color('primary')
                ->url(SubscriberResource::getUrl()),
        ];
    }
}
