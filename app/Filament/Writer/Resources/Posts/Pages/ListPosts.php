<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts\Pages;

use App\Filament\Writer\Resources\Posts\PostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/** The writer's articles. */
class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New article'),
        ];
    }
}
