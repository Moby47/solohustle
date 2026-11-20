<?php

declare(strict_types=1);

namespace App\Filament\Resources\Jobs\Schemas;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkMode;
use App\Models\Job;
use App\Support\ImageProcessor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** The job editor: what the job is on the left, status and dates on the right. */
class JobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        self::roleSection(),
                        self::descriptionSection(),
                        self::applySection(),
                    ]),
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        self::statusSection(),
                        self::companySection(),
                        self::seoSection(),
                    ]),
            ]);
    }

    private static function roleSection(): Section
    {
        return Section::make('The role')
            ->schema([
                TextInput::make('title')
                    ->label('Job title')
                    ->required()
                    ->maxLength(150),
                Grid::make(2)->schema([
                    Select::make('job_category_id')
                        ->label('Category')
                        ->relationship('jobCategory', 'name', fn ($query) => $query->orderBy('sort'))
                        ->preload()
                        ->required(),
                    Select::make('region_id')
                        ->label('Where applicants can be')
                        ->relationship('region', 'name', fn ($query) => $query->orderBy('sort'))
                        ->preload()
                        ->required(),
                ]),
                ToggleButtons::make('work_mode')
                    ->label('Work mode')
                    ->options(WorkMode::class)
                    ->default(WorkMode::Remote)
                    ->inline()
                    ->required(),
                ToggleButtons::make('employment_type')
                    ->label('Type')
                    ->options(EmploymentType::class)
                    ->default(EmploymentType::FullTime)
                    ->inline()
                    ->required(),
                ToggleButtons::make('experience_level')
                    ->label('Experience')
                    ->options(ExperienceLevel::class)
                    ->default(ExperienceLevel::Any)
                    ->inline()
                    ->required(),
                Grid::make(2)->schema([
                    TextInput::make('location_note')
                        ->label('Location details')
                        ->placeholder('e.g. Remote, UK time zones')
                        ->helperText('Shown on the job. Optional.')
                        ->maxLength(150),
                    Select::make('country_code')
                        ->label('Country')
                        ->options(config('countries'))
                        ->searchable()
                        ->helperText('Optional. Helps the job show up in Google\'s job search. Leave empty for jobs open to anywhere.'),
                ]),
                TextInput::make('salary_text')
                    ->label('Pay')
                    ->placeholder('e.g. $40–55k a year, or $25 an hour')
                    ->helperText('Optional. Write it the way the employer shows it.')
                    ->maxLength(100),
            ]);
    }

    private static function descriptionSection(): Section
    {
        return Section::make('Description')
            ->schema([
                Textarea::make('summary')
                    ->label('Summary')
                    ->helperText('In your own words: what the job is and who it suits. Never copy the employer\'s description.')
                    ->required()
                    ->rows(6)
                    ->maxLength(3000),
                Textarea::make('requirements')
                    ->label('Requirements')
                    ->helperText('One per line. Each line becomes a bullet point.')
                    ->rows(6)
                    ->maxLength(3000),
            ]);
    }

    private static function applySection(): Section
    {
        return Section::make('Applying')
            ->columns(2)
            ->schema([
                TextInput::make('apply_url')
                    ->label('Apply link')
                    ->helperText('The employer\'s own page where people apply.')
                    ->placeholder('https://')
                    ->url()
                    ->rule('url:http,https')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                TextInput::make('source_name')
                    ->label('Found on')
                    ->placeholder('e.g. Company careers page')
                    ->maxLength(100),
                TextInput::make('source_url')
                    ->label('Source link')
                    ->placeholder('https://')
                    ->url()
                    ->rule('url:http,https')
                    ->maxLength(500),
            ]);
    }

    private static function statusSection(): Section
    {
        return Section::make('Status')
            ->schema([
                ToggleButtons::make('status')
                    ->hiddenLabel()
                    ->options(JobStatus::class)
                    ->default(JobStatus::Open)
                    ->inline()
                    ->required(),
                DatePicker::make('posted_at')
                    ->label('Posted on')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('j M Y'),
                DatePicker::make('closes_at')
                    ->label('Closing date')
                    ->helperText('Optional. The job closes automatically the day after this date.')
                    ->native(false)
                    ->displayFormat('j M Y'),
                TextEntry::make('last_verified_at')
                    ->label('Last checked')
                    ->state(fn (?Job $record): string => $record?->last_verified_at
                        ? $record->last_verified_at->format('j M Y').' ('.$record->last_verified_at->diffForHumans().')'
                        : 'Today, when you save')
                    ->color(fn (?Job $record): ?string => $record?->isDueForRecheck() ? 'warning' : null)
                    ->helperText(fn (?Job $record): ?string => $record?->isDueForRecheck()
                        ? 'Over '.Job::RECHECK_AFTER_DAYS.' days ago. Check the employer\'s page, then click "Mark verified".'
                        : null),
                Toggle::make('is_featured')
                    ->label('Feature this job')
                    ->helperText('Featured jobs are shown first.'),
            ]);
    }

    private static function companySection(): Section
    {
        return Section::make('Company')
            ->schema([
                TextInput::make('company_name')
                    ->label('Company name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('company_url')
                    ->label('Company website')
                    ->placeholder('https://')
                    ->url()
                    ->rule('url:http,https')
                    ->maxLength(500),
                FileUpload::make('company_logo')
                    ->label('Logo')
                    ->helperText('Optional. A square logo looks best.')
                    ->image()
                    ->maxSize(2048)
                    ->disk(config('media.disk'))
                    ->visibility('public')
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ImageProcessor::class)
                        ->storeContained($file, 'logos', 200, 200)),
            ]);
    }

    private static function seoSection(): Section
    {
        return Section::make('Search engines')
            ->description('Optional. Leave empty to use the job title and summary.')
            ->collapsed()
            ->schema([
                TextInput::make('seo_title')->label('Title in search results')->maxLength(70),
                Textarea::make('meta_description')->label('Description in search results')->rows(3)->maxLength(160),
            ]);
    }
}
