<?php

declare(strict_types=1);

namespace App\Filament\Resources\Writers\RelationManagers;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** A writer's articles, linking through to the article editor. */
class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    protected static ?string $title = 'Articles';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Article')->searchable()->wrap(),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->label('Published')->date()->placeholder('Not yet'),
                TextColumn::make('updated_at')->label('Last edited')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PostStatus::class),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(fn (Post $record): string => $record->status === PostStatus::Pending ? 'Review' : 'Open')
                    ->icon(Heroicon::OutlinedArrowRight)
                    ->url(fn (Post $record): string => PostResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('No articles yet');
    }
}
