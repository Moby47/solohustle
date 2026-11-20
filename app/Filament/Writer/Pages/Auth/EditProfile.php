<?php

declare(strict_types=1);

namespace App\Filament\Writer\Pages\Auth;

use App\Support\ImageProcessor;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** The writer's account details plus the public author profile shown on their author page. */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->schema([
                    $this->getNameFormComponent(),
                    $this->getEmailFormComponent(),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                    $this->getCurrentPasswordFormComponent(),
                ]),
            Section::make('Public profile')
                ->description('Shown on your author page and under your articles.')
                ->schema([
                    FileUpload::make('photo')
                        ->label('Photo')
                        ->helperText('A clear head-and-shoulders photo works best. Max 5 MB. It is cropped square automatically.')
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->imageAspectRatio('1:1')
                        ->automaticallyCropImagesToAspectRatio()
                        ->automaticallyResizeImagesMode('cover')
                        ->automaticallyResizeImagesToWidth('800')
                        ->automaticallyResizeImagesToHeight('800')
                        ->rule('dimensions:max_width=8000,max_height=8000')
                        ->disk(config('media.disk'))
                        ->visibility('public')
                        ->maxSize(5120)
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(ImageProcessor::class)
                            ->storeCropped($file, 'authors', 400, 400)),
                    TextInput::make('headline')
                        ->label('Headline')
                        ->helperText('One line about you, e.g. "Freelance SEO writer for SaaS brands".')
                        ->maxLength(120),
                    Textarea::make('bio')
                        ->label('Short bio')
                        ->helperText('A few sentences about your experience and what you write about.')
                        ->rows(5)
                        ->maxLength(1000),
                    TextInput::make('website_url')
                        ->label('Website or portfolio')
                        ->url()
                        ->rule('url:http,https')
                        ->placeholder('https://')
                        ->maxLength(255),
                    TextInput::make('linkedin_url')
                        ->label('LinkedIn')
                        ->url()
                        ->rule('url:http,https')
                        ->placeholder('https://linkedin.com/in/...')
                        ->maxLength(255),
                    TextInput::make('x_url')
                        ->label('X (Twitter)')
                        ->url()
                        ->rule('url:http,https')
                        ->placeholder('https://x.com/...')
                        ->maxLength(255),
                ]),
        ]);
    }
}
