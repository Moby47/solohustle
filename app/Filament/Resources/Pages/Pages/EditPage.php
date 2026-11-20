<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/** Edit a page, with a shortcut to see it on the site. */
class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(fn (Page $record): string => $record->is_published ? 'View on site' : 'Preview')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Page $record): string => $record->url())
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->modalDescription('The page is removed from the site straight away. Menu links pointing to it will stop working.'),
        ];
    }
}
