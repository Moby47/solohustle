<?php

declare(strict_types=1);

namespace App\Filament\Resources\JobCategories;

use App\Filament\Resources\JobCategories\Pages\ManageJobCategories;
use App\Models\JobCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/** Kinds of job, each with a landing page at /jobs/{slug}. */
class JobCategoryResource extends Resource
{
    protected static ?string $model = JobCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Jobs';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(60)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                        if (blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Web address')
                    ->prefix(url('/jobs').'/')
                    ->required()
                    ->maxLength(60)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->notIn(['remote'])
                    ->validationMessages([
                        'regex' => 'Use only lowercase letters, numbers and dashes.',
                        'not_in' => '"remote" is used by the Remote jobs page. Please choose another address.',
                    ])
                    ->unique(ignoreRecord: true),
                Textarea::make('intro')
                    ->label('Introduction')
                    ->helperText('A short paragraph shown at the top of this category\'s jobs page.')
                    ->rows(3)
                    ->maxLength(500),
                Select::make('related_category_id')
                    ->label('Related blog category')
                    ->relationship('relatedCategory', 'name', fn ($query) => $query->orderBy('sort'))
                    ->preload()
                    ->helperText('Optional. Its latest guides are shown on these jobs, and its articles link back to these jobs.'),
                Section::make('Search engines')
                    ->description('Optional. Leave empty to use the name and introduction.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('seo_title')->label('Title in search results')->maxLength(70),
                        Textarea::make('meta_description')->label('Description in search results')->rows(2)->maxLength(160),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->description('Drag the handles to change the order categories appear in.')
            ->columns([
                TextColumn::make('name')
                    ->label('Category')
                    ->description(fn (JobCategory $record): string => '/jobs/'.$record->slug)
                    ->searchable(),
                TextColumn::make('relatedCategory.name')->label('Related guides')->placeholder('—'),
                TextColumn::make('jobs_count')->label('Jobs')->counts('jobs'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (JobCategory $record): bool => $record->jobs_count > 0)
                    ->tooltip(fn (JobCategory $record): ?string => $record->jobs_count > 0 ? 'Move or delete its jobs first' : null),
            ])
            ->emptyStateHeading('No job categories yet');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageJobCategories::route('/'),
        ];
    }
}
