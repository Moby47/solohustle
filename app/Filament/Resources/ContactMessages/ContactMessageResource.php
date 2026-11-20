<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\ContactMessages\Schemas\ContactMessageInfolist;
use App\Filament\Resources\ContactMessages\Tables\ContactMessagesTable;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** The owner's Inbox: messages sent through the contact form. */
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $modelLabel = 'message';

    protected static ?string $navigationLabel = 'Inbox';

    protected static ?string $slug = 'inbox';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = ContactMessage::unread()->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Unread messages';
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactMessagesTable::configure($table);
    }

    public static function replyAction(): Action
    {
        return Action::make('reply')
            ->label('Reply by email')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->url(fn (ContactMessage $record): string => $record->replyUrl());
    }

    public static function markUnreadAction(): Action
    {
        return Action::make('markUnread')
            ->label('Mark as unread')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (ContactMessage $record): bool => $record->isRead())
            ->action(function (ContactMessage $record): void {
                $record->markUnread();
                Notification::make()->success()->title('Marked as unread')->send();
            });
    }

    public static function markReadAction(): Action
    {
        return Action::make('markRead')
            ->label('Mark as read')
            ->icon(Heroicon::OutlinedEnvelopeOpen)
            ->color('gray')
            ->visible(fn (ContactMessage $record): bool => ! $record->isRead())
            ->action(fn (ContactMessage $record) => $record->markRead());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
