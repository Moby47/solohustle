<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Support\ImageProcessor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** The owner's article editor: writing on the left, settings on the right. */
class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                self::reviewCallout(),
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        self::articleSection(),
                        self::answerBoxesSection(),
                    ]),
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        self::publishingSection(),
                        self::coverSection(),
                        self::seoSection(),
                    ]),
            ]);
    }

    /**
     * Shows where a guest submission stands, so the owner knows what to do next.
     */
    private static function reviewCallout(): Callout
    {
        return Callout::make(fn (?Post $record): string => match ($record?->status) {
            PostStatus::Pending => 'Awaiting your review',
            PostStatus::ChangesRequested => 'Sent back to the writer for changes',
            PostStatus::Rejected => 'Rejected',
            default => 'Guest submission',
        })
            ->description(function (?Post $record): string {
                $submitted = "Submitted by {$record?->author?->name} on {$record?->submitted_at?->format('j M Y, H:i')}. They confirmed it is their own original work and theirs to publish.";

                return match ($record?->status) {
                    PostStatus::Pending => $submitted.' Read it below, then use the buttons at the top to publish it, ask for changes or reject it.',
                    PostStatus::ChangesRequested, PostStatus::Rejected => $submitted.' Your note: "'.$record->review_note.'"',
                    default => $submitted,
                };
            })
            ->status(fn (?Post $record): string => match ($record?->status) {
                PostStatus::Pending => 'warning',
                PostStatus::Rejected => 'danger',
                default => 'info',
            })
            ->visible(fn (?Post $record): bool => $record?->submitted_at !== null && $record->status !== PostStatus::Published)
            ->columnSpanFull();
    }

    /**
     * Title, summary and text. Writers don't see the web address; it's made from the title and the owner can change it.
     */
    public static function articleSection(bool $withSlug = true): Section
    {
        return Section::make('Article')
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(150)
                    ->live(onBlur: $withSlug)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) use ($withSlug): void {
                        if ($withSlug && (blank($get('slug')) || $get('slug') === Str::slug((string) $old))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->visible($withSlug)
                    ->label('Web address')
                    ->prefix(fn (Get $get): string => url('/').'/'.(Category::find($get('category_id'))?->slug ?? 'category').'/')
                    ->required()
                    ->maxLength(150)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->validationMessages(['regex' => 'Use only lowercase letters, numbers and dashes.'])
                    ->unique(ignoreRecord: true)
                    ->helperText('Filled in from the title. Changing it on a live article automatically redirects the old address.'),
                Textarea::make('excerpt')
                    ->label('Summary')
                    ->helperText('One or two sentences shown on article cards and under the title.')
                    ->rows(2)
                    ->maxLength(300),
                RichEditor::make('body')
                    ->label('Article text')
                    ->helperText('Tip: phrase headings as questions where natural, answer straight away, keep paragraphs short, use lists and tables, give real examples and link to your sources.')
                    ->fileAttachmentsDisk(config('media.disk'))
                    ->fileAttachmentsVisibility('public')
                    ->saveUploadedFileAttachmentUsing(fn (TemporaryUploadedFile $file): string => app(ImageProcessor::class)
                        ->storeScaled($file, 'posts', 1600))
                    ->extraInputAttributes(['style' => 'min-height: 32rem']),
            ]);
    }

    public static function answerBoxesSection(): Section
    {
        return Section::make('Answer boxes')
            ->description('Optional. They help readers, Google and AI assistants find and quote the answer quickly.')
            ->collapsible()
            ->schema([
                Textarea::make('quick_answer')
                    ->label('Quick answer')
                    ->helperText('A direct 40–60 word answer to the article\'s main question. Shown in a box under the title.')
                    ->rows(3)
                    ->maxLength(600)
                    ->live(debounce: 500)
                    ->hint(fn (?string $state): ?string => self::wordCountHint($state))
                    ->hintColor(fn (?string $state): string => self::isQuickAnswerLengthGood($state) ? 'success' : 'warning'),
                Textarea::make('key_takeaway')
                    ->label('Key takeaway')
                    ->helperText('The one thing readers should remember. Shown near the end.')
                    ->rows(2)
                    ->maxLength(400),
                Repeater::make('faqs')
                    ->label('Frequently asked questions')
                    ->helperText('Real questions readers ask, each with a short answer. Shown near the end and marked up for Google.')
                    ->schema([
                        TextInput::make('question')->label('Question')->required()->maxLength(200),
                        Textarea::make('answer')->label('Answer')->required()->rows(3)->maxLength(1000),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->collapsible()
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel('Add a question'),
                Repeater::make('sources')
                    ->label('Sources')
                    ->helperText('Studies, data or pages you relied on. Shown as a numbered list at the end.')
                    ->schema([
                        TextInput::make('title')->label('Name')->required()->maxLength(200),
                        TextInput::make('url')->label('Link')->required()->url()->rule('url:http,https')->maxLength(500),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel('Add a source'),
            ]);
    }

    private static function publishingSection(): Section
    {
        return Section::make('Publishing')
            ->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(PostStatus::class)
                    ->default(PostStatus::Draft)
                    ->required()
                    ->native(false),
                DateTimePicker::make('published_at')
                    ->label('Publish date')
                    ->helperText('Set automatically when you publish. Pick a future date to schedule it.')
                    ->seconds(false),
                Select::make('author_id')
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->default(fn (): ?int => auth()->id())
                    ->searchable()
                    ->preload()
                    ->required(),
                self::categorySelect(),
                self::tagsSelect(canCreate: true),
                Toggle::make('is_featured')
                    ->label('Feature on the homepage')
                    ->helperText('Featured articles appear in the "Featured guides" section.'),
            ]);
    }

    public static function categorySelect(): Select
    {
        return Select::make('category_id')
            ->label('Category')
            ->relationship('category', 'name', fn ($query) => $query->orderBy('sort'))
            ->preload()
            ->required()
            ->live();
    }

    public static function tagsSelect(bool $canCreate): Select
    {
        $select = Select::make('tags')
            ->label('Tags')
            ->relationship('tags', 'name')
            ->multiple()
            ->searchable()
            ->preload();

        if ($canCreate) {
            $select->createOptionForm([
                TextInput::make('name')->label('New tag')->required()->maxLength(50),
            ]);
        }

        return $select;
    }

    public static function coverSection(): Section
    {
        return Section::make('Cover image')
            ->schema([
                FileUpload::make('cover_image')
                    ->hiddenLabel()
                    ->helperText('Landscape, at least 1600 × 900 pixels. It is cropped to 16:9 and optimised automatically.')
                    ->image()
                    ->imageEditor()
                    ->imageAspectRatio('16:9')
                    ->automaticallyCropImagesToAspectRatio()
                    // Shrunk in the browser first: faster uploads and no giant photos on the server.
                    ->automaticallyResizeImagesMode('cover')
                    ->automaticallyResizeImagesToWidth('1920')
                    ->automaticallyResizeImagesToHeight('1080')
                    ->rule('dimensions:max_width=8000,max_height=8000')
                    ->maxSize(8192)
                    ->disk(config('media.disk'))
                    ->visibility('public')
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ImageProcessor::class)
                        ->storeCropped($file, 'covers', Post::COVER_WIDTH, Post::COVER_HEIGHT, Post::COVER_SMALLER_WIDTHS)),
                TextInput::make('cover_alt')
                    ->label('Image description')
                    ->helperText('Describe the picture for people who can\'t see it, e.g. "Woman working on a laptop at a kitchen table".')
                    ->requiredWith('cover_image')
                    ->validationMessages(['required_with' => 'Please describe the image.'])
                    ->maxLength(200),
            ]);
    }

    private static function seoSection(): Section
    {
        return Section::make('Search engines')
            ->description('Optional. Leave empty to use the title and summary.')
            ->collapsed()
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
            ]);
    }

    private static function wordCountHint(?string $text): ?string
    {
        if (blank($text)) {
            return null;
        }

        return str_word_count($text).' words (aim for 40–60)';
    }

    private static function isQuickAnswerLengthGood(?string $text): bool
    {
        $words = str_word_count((string) $text);

        return $words >= 40 && $words <= 60;
    }
}
