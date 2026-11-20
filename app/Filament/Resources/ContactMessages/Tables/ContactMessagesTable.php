<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/** The Inbox list. Unread messages are bold with a dot, like an email app. */
class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->state(fn (ContactMessage $record): bool => ! $record->isRead())
                    ->icon(fn (bool $state): ?Heroicon => $state ? Heroicon::Envelope : null)
                    ->color('primary')
                    ->tooltip(fn (ContactMessage $record): ?string => $record->isRead() ? null : 'Unread'),
                TextColumn::make('name')
                    ->label('From')
                    ->description(fn (ContactMessage $record): string => $record->email)
                    ->weight(fn (ContactMessage $record): ?FontWeight => $record->isRead() ? null : FontWeight::Bold)
                    ->searchable(['name', 'email']),
                TextColumn::make('subject')
                    ->label('Message')
                    ->description(fn (ContactMessage $record): string => Str::limit($record->message, 90))
                    ->weight(fn (ContactMessage $record): ?FontWeight => $record->isRead() ? null : FontWeight::Bold)
                    ->searchable(['subject', 'message'])
                    ->wrap(),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Status')
                    ->nullable()
                    ->trueLabel('Read')
                    ->falseLabel('Unread'),
            ])
            ->recordActions([
                ViewAction::make()->label('Open'),
                ActionGroup::make([
                    ContactMessageResource::replyAction(),
                    ContactMessageResource::markReadAction(),
                    ContactMessageResource::markUnreadAction(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markRead')
                        ->label('Mark as read')
                        ->icon(Heroicon::OutlinedEnvelopeOpen)
                        ->action(fn (Collection $records) => $records->each(fn (ContactMessage $m) => $m->markRead()))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('markUnread')
                        ->label('Mark as unread')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->action(fn (Collection $records) => $records->each(fn (ContactMessage $m) => $m->markUnread()))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No messages yet')
            ->emptyStateDescription('Messages sent through the contact form on your Contact page arrive here, and by email.');
    }
}
