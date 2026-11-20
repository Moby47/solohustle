<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Read-only view of an article once it has left the writer's hands. */
class WriterPostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Callout::make(fn (Post $record): string => match ($record->status) {
                    PostStatus::Pending => 'Awaiting review',
                    PostStatus::Published => 'Published',
                    PostStatus::Rejected => 'Not accepted',
                    default => $record->status->getLabel(),
                })
                    ->description(fn (Post $record): string => match ($record->status) {
                        PostStatus::Pending => "Submitted on {$record->submitted_at?->format('j M Y')}. The editor will read it soon and you'll get an email with their decision. You can't edit it while it's being reviewed.",
                        PostStatus::Published => "Live since {$record->published_at?->format('j M Y')}. To change anything, please contact us.",
                        PostStatus::Rejected => "The editor's note: \"{$record->review_note}\"",
                        default => '',
                    })
                    ->status(fn (Post $record): string => match ($record->status) {
                        PostStatus::Published => 'success',
                        PostStatus::Rejected => 'danger',
                        default => 'warning',
                    }),
                Section::make()
                    ->schema([
                        TextEntry::make('title')->label('Title')->size('lg')->weight('bold'),
                        TextEntry::make('category.name')->label('Category'),
                        TextEntry::make('excerpt')->label('Summary')->placeholder('No summary'),
                        TextEntry::make('body')
                            ->label('Article text')
                            ->state(fn (Post $record) => $record->renderedBody())
                            ->html()
                            ->prose(),
                    ]),
            ]);
    }
}
