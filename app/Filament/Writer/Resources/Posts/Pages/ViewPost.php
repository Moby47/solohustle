<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Writer\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Read-only view of a submitted, published or rejected article.
 *
 * @property Post $record
 */
class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewOnSite')
                ->label('View on site')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (): string => $this->record->url())
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->status === PostStatus::Published),
            EditAction::make(),
        ];
    }
}
