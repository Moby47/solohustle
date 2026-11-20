<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subscribers;

use App\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Models\Subscriber;
use App\Support\Newsletter;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/** Newsletter subscribers, always kept on this site (and copied to Brevo when it's connected). */
class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'email';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')
                    ->label('Subscriber')
                    ->description(fn (Subscriber $record): ?string => $record->name)
                    ->searchable(['email', 'name'])
                    ->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Subscriber $record): string => $record->statusLabel())
                    ->color(fn (string $state): string => match ($state) {
                        'Subscribed' => 'success',
                        'Unsubscribed' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('source')->label('Signed up on')->placeholder('—')->toggleable(),
                IconColumn::make('brevo_synced_at')
                    ->label('In Brevo')
                    ->state(fn (Subscriber $record): bool => $record->brevo_synced_at !== null)
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')->label('Joined')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['subscribed' => 'Subscribed', 'pending' => 'Waiting for confirmation', 'unsubscribed' => 'Unsubscribed'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'subscribed' => $query->active(),
                        'pending' => $query->whereNull('confirmed_at')->whereNull('unsubscribed_at'),
                        'unsubscribed' => $query->whereNotNull('unsubscribed_at'),
                        default => $query,
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->modalDescription('They are removed from this site, and from your Brevo list if it\'s connected.')
                    ->before(fn (Subscriber $record) => app(Newsletter::class)->syncToBrevo($record, subscribe: false)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (Collection $records) => $records->each(fn (Subscriber $subscriber) => app(Newsletter::class)->syncToBrevo($subscriber, subscribe: false))),
                ]),
            ])
            ->emptyStateHeading('No subscribers yet')
            ->emptyStateDescription('People who sign up for the newsletter appear here once they\'ve confirmed by email.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscribers::route('/'),
        ];
    }
}
