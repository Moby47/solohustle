<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Models\Post;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** The writer's editor: the same writing fields as the owner's, without publishing controls. */
class WriterPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Callout::make('The editor asked for some changes')
                    ->description(fn (?Post $record): string => '"'.$record?->review_note.'" Make the changes, then click "Submit for review" again.')
                    ->info()
                    ->visible(fn (?Post $record): bool => $record?->status === PostStatus::ChangesRequested)
                    ->columnSpanFull(),
                Callout::make('Your draft')
                    ->description('Your work is saved as a draft. Only you can see it until you submit it for review. Use "Preview" at the top to see how it will look.')
                    ->visible(fn (?Post $record): bool => $record?->status === PostStatus::Draft)
                    ->columnSpanFull(),
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        PostForm::articleSection(withSlug: false),
                        PostForm::answerBoxesSection(),
                    ]),
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Details')
                            ->schema([
                                PostForm::categorySelect(),
                                PostForm::tagsSelect(canCreate: false),
                            ]),
                        PostForm::coverSection(),
                    ]),
            ]);
    }
}
