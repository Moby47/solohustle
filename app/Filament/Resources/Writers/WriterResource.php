<?php

declare(strict_types=1);

namespace App\Filament\Resources\Writers;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Writers\Pages\ListWriters;
use App\Filament\Resources\Writers\Pages\ViewWriter;
use App\Filament\Resources\Writers\RelationManagers\PostsRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Guest writers: see who they are and what they've written, and suspend them if needed. */
class WriterResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'writer';

    protected static ?string $slug = 'writers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::Writer)
            ->withCount([
                'posts',
                'posts as published_posts_count' => fn (Builder $query) => $query->where('status', PostStatus::Published),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Profile')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)->schema([
                            ImageEntry::make('photo')->hiddenLabel()->disk(config('media.disk'))->circular()->imageHeight(96),
                            TextEntry::make('headline')->hiddenLabel()->weight('bold')->placeholder('No headline yet'),
                        ]),
                        TextEntry::make('bio')->label('Bio')->placeholder('No bio yet'),
                        TextEntry::make('website_url')->label('Website')->url(fn (?string $state) => $state)->openUrlInNewTab()->placeholder('—'),
                        TextEntry::make('linkedin_url')->label('LinkedIn')->url(fn (?string $state) => $state)->openUrlInNewTab()->placeholder('—'),
                        TextEntry::make('x_url')->label('X (Twitter)')->url(fn (?string $state) => $state)->openUrlInNewTab()->placeholder('—'),
                    ]),
                Section::make('Account')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('account_status')
                            ->label('Status')
                            ->badge()
                            ->state(fn (User $record): string => $record->isSuspended() ? 'Suspended' : 'Active')
                            ->color(fn (string $state): string => $state === 'Suspended' ? 'danger' : 'success'),
                        TextEntry::make('suspended_at')->label('Suspended on')->dateTime('j M Y')->visible(fn (User $record) => $record->isSuspended()),
                        TextEntry::make('email')->label('Email')->copyable(),
                        IconEntry::make('email_verified_at')->label('Email verified')->boolean()->state(fn (User $record): bool => $record->hasVerifiedEmail()),
                        TextEntry::make('created_at')->label('Joined')->date('j M Y'),
                        TextEntry::make('published_posts_count')->label('Published articles'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('photo')->label('')->disk(config('media.disk'))->circular()->imageSize(40),
                TextColumn::make('name')
                    ->label('Writer')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('published_posts_count')->label('Published')->sortable(),
                TextColumn::make('posts_count')->label('All articles')->sortable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->state(fn (User $record): bool => $record->hasVerifiedEmail()),
                TextColumn::make('account_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (User $record): string => $record->isSuspended() ? 'Suspended' : 'Active')
                    ->color(fn (string $state): string => $state === 'Suspended' ? 'danger' : 'success'),
                TextColumn::make('created_at')->label('Joined')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('suspended_at')
                    ->label('Status')
                    ->nullable()
                    ->trueLabel('Suspended')
                    ->falseLabel('Active'),
                TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
                static::suspendAction(),
                static::unsuspendAction(),
            ])
            ->emptyStateHeading('No writers yet')
            ->emptyStateDescription('Writers appear here when they sign up through the "Publish with us" page.');
    }

    public static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label('Suspend')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => ! $record->isSuspended())
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Suspend {$record->name}?")
            ->modalDescription('They won\'t be able to sign in, and their author page will be hidden. Their published articles stay on the site. You can undo this at any time.')
            ->modalSubmitActionLabel('Suspend')
            ->action(function (User $record): void {
                $record->forceFill(['suspended_at' => now()])->save();
                Notification::make()->success()->title("{$record->name} is suspended")->send();
            });
    }

    public static function unsuspendAction(): Action
    {
        return Action::make('unsuspend')
            ->label('Unsuspend')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (User $record): bool => $record->isSuspended())
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Unsuspend {$record->name}?")
            ->modalDescription('They can sign in again, and their author page comes back if they have published articles.')
            ->modalSubmitActionLabel('Unsuspend')
            ->action(function (User $record): void {
                $record->forceFill(['suspended_at' => null])->save();
                Notification::make()->success()->title("{$record->name} can sign in again")->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            PostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWriters::route('/'),
            'view' => ViewWriter::route('/{record}'),
        ];
    }
}
