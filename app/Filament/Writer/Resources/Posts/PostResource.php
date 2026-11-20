<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts;

use App\Filament\Writer\Resources\Posts\Pages\CreatePost;
use App\Filament\Writer\Resources\Posts\Pages\EditPost;
use App\Filament\Writer\Resources\Posts\Pages\ListPosts;
use App\Filament\Writer\Resources\Posts\Pages\ViewPost;
use App\Filament\Writer\Resources\Posts\Schemas\WriterPostForm;
use App\Filament\Writer\Resources\Posts\Schemas\WriterPostInfolist;
use App\Filament\Writer\Resources\Posts\Tables\WriterPostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** A writer's own articles. Everything here is limited to the signed-in writer; the Post policy enforces the rest. */
class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $modelLabel = 'article';

    protected static ?string $navigationLabel = 'My articles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * @return Builder<Post>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('author_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return WriterPostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WriterPostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WriterPostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
