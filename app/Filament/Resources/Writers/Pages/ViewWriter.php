<?php

declare(strict_types=1);

namespace App\Filament\Resources\Writers\Pages;

use App\Filament\Resources\Writers\WriterResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * One writer's profile and articles.
 *
 * @property User $record
 */
class ViewWriter extends ViewRecord
{
    protected static string $resource = WriterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('authorPage')
                ->label('View author page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => $this->record->authorUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->hasAuthorPage()),
            WriterResource::suspendAction(),
            WriterResource::unsuspendAction(),
        ];
    }
}
