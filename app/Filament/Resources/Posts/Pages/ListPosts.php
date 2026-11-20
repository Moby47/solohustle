<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** Articles list with quick tabs by status. */
class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New article'),
        ];
    }

    public function getTabs(): array
    {
        $pending = Post::where('status', PostStatus::Pending)->count();

        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Awaiting review')
                ->badge($pending ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PostStatus::Pending)),
            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PostStatus::Published)),
            'drafts' => Tab::make('Drafts')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [PostStatus::Draft, PostStatus::ChangesRequested])),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PostStatus::Rejected)),
        ];
    }
}
