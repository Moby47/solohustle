<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** The Inbox. */
class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    public function getTabs(): array
    {
        $unread = ContactMessage::unread()->count();

        return [
            'all' => Tab::make('All'),
            'unread' => Tab::make('Unread')
                ->badge($unread ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('read_at')),
        ];
    }
}
