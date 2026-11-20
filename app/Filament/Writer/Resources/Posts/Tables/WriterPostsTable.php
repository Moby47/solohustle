<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/** The writer's list of their own articles. */
class WriterPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Article')
                    ->description(fn (Post $record): string => $record->category->name)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->description(fn (Post $record): ?string => in_array($record->status, [PostStatus::ChangesRequested, PostStatus::Rejected], true)
                        ? Str::limit((string) $record->review_note, 80)
                        : null),
                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make()
                    ->visible(fn (Post $record): bool => ! $record->isEditableByWriter()),
            ])
            ->recordUrl(fn (Post $record): string => $record->writerUrl())
            ->emptyStateHeading('You haven\'t written anything yet')
            ->emptyStateDescription('Start a draft whenever you like. Nothing is shared until you submit it for review.')
            ->emptyStateActions([
                CreateAction::make()->label('Write your first article'),
            ]);
    }
}
