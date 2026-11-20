<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use App\Rules\PublicSlug;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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

/** Blog sections (Career, Freelancing, …). Drag to set their order in menus and lists. */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

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
                    ->prefix(url('/').'/')
                    ->required()
                    ->maxLength(60)
                    ->rule(new PublicSlug(Category::class))
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Introduction')
                    ->helperText('A short paragraph shown at the top of the category page.')
                    ->rows(3)
                    ->maxLength(500),
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
                    ->description(fn (Category $record): string => '/'.$record->slug)
                    ->searchable(),
                TextColumn::make('posts_count')
                    ->label('Articles')
                    ->counts('posts'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Category $record): bool => $record->posts_count > 0)
                    ->tooltip(fn (Category $record): ?string => $record->posts_count > 0
                        ? 'Move or delete its articles first'
                        : null),
            ])
            ->emptyStateHeading('No categories yet');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
