<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use App\Rules\PublicSlug;
use App\Support\ImageProcessor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** The add/edit form for pages. */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Content')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                // Follow the title until the owner edits the address by hand.
                                if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('Web address')
                            ->prefix(url('/').'/')
                            ->required()
                            ->maxLength(120)
                            ->rule(new PublicSlug(Page::class))
                            ->unique(ignoreRecord: true)
                            ->helperText(fn (?Page $record): string => $record?->is_published
                                ? 'If you change this, visitors using the old address are sent to the new one automatically.'
                                : 'Filled in from the title. Lowercase letters, numbers and dashes only.'),
                        RichEditor::make('body')
                            ->label('Page text')
                            ->fileAttachmentsDisk(config('media.disk'))
                            ->fileAttachmentsVisibility('public')
                            ->saveUploadedFileAttachmentUsing(fn (TemporaryUploadedFile $file): string => app(ImageProcessor::class)
                                ->storeScaled($file, 'pages', 1600))
                            ->extraInputAttributes(['style' => 'min-height: 24rem']),
                    ]),
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Visibility')
                            ->schema([
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->helperText('Off: only you can see the page (open it from the list to preview). On: everyone can.'),
                            ]),
                        Section::make('Search engines')
                            ->description('Optional. Leave empty to use the title and the default description.')
                            ->collapsible()
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('Title in search results')
                                    ->maxLength(70)
                                    ->helperText('Up to about 60 characters.'),
                                Textarea::make('meta_description')
                                    ->label('Description in search results')
                                    ->rows(3)
                                    ->maxLength(160)
                                    ->helperText('One or two sentences, up to 160 characters.'),
                            ]),
                    ]),
            ]);
    }
}
