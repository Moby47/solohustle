<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Guest articles waiting for review, oldest first, shown only when there are some. */
class PendingSubmissions extends TableWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Awaiting your review';

    public static function canView(): bool
    {
        return Post::where('status', PostStatus::Pending)->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Post::query()->where('status', PostStatus::Pending)->with(['author', 'category']))
            ->defaultSort('submitted_at')
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label('Article')->description(fn (Post $record): string => 'by '.$record->author->name)->wrap(),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('submitted_at')->label('Submitted')->since(),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->button()
                    ->color('warning')
                    ->url(fn (Post $record): string => PostResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
