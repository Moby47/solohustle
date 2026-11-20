<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** The list of articles in the dashboard. */
class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'author']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->disk(config('media.disk'))
                    ->getStateUsing(fn (Post $record): ?string => $record->cover_image
                        ? str_replace('.webp', '-480.webp', $record->cover_image)
                        : null)
                    ->imageWidth(80)
                    ->imageHeight(45),
                TextColumn::make('title')
                    ->label('Article')
                    ->description(fn (Post $record): string => $record->category->name.' · by '.$record->author->name)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->date()
                    ->placeholder('Not yet')
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('')
                    ->trueColor('warning'),
                TextColumn::make('updated_at')
                    ->label('Last edited')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(PostStatus::class),
                SelectFilter::make('category')->relationship('category', 'name')->preload(),
                SelectFilter::make('author')->relationship('author', 'name')->searchable()->preload(),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(fn (Post $record): string => $record->status === PostStatus::Pending ? 'Review' : 'Edit')
                    ->color(fn (Post $record): string => $record->status === PostStatus::Pending ? 'warning' : 'primary'),
            ])
            ->emptyStateHeading('No articles yet')
            ->emptyStateDescription('Write your first article, or wait for guest writers to submit theirs.');
    }
}
