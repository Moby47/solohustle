<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\LinkPicker;
use App\Support\MailSettings;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

/**
 * The owner's Site settings: one page, one tab per area.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(Settings $settings): void
    {
        $this->form->fill($this->withoutSecrets($settings->all()));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->persistTabInQueryString()
                    ->tabs([
                        $this->generalTab(),
                        $this->homepageTab(),
                        $this->navigationTab(),
                        $this->socialTab(),
                        $this->seoTab(),
                        $this->articleTab(),
                        $this->jobsTab(),
                        $this->publishTab(),
                        $this->integrationsTab(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save settings')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(Settings $settings): void
    {
        $settings->save($this->form->getState());

        $this->form->fill($this->withoutSecrets($settings->all()));

        Notification::make()->success()->title('Settings saved')->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Send test email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->modalDescription('Sends a short message using the saved email settings. Save any changes first.')
                ->modalSubmitActionLabel('Send')
                ->schema([
                    TextInput::make('email')
                        ->label('Send to')
                        ->email()
                        ->required()
                        ->default(fn (): ?string => auth()->user()?->email),
                ])
                ->action(fn (array $data) => $this->sendTestEmail($data['email'])),
        ];
    }

    private function sendTestEmail(string $to): void
    {
        app(MailSettings::class)->apply();
        $siteName = setting('general.site_name');

        try {
            Mail::raw(
                "This is a test email from {$siteName}. If you can read it, email sending works.",
                fn ($message) => $message->to($to)->subject("Test email from {$siteName}"),
            );
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title('The test email could not be sent')
                ->body('Check the email settings and try again. Details: '.$exception->getMessage())
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title("Test email sent to {$to}")
            ->body(config('mail.default') === 'log'
                ? "Email sending isn't set up yet, so the message was written to the site's log file instead of being delivered."
                : null)
            ->send();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function withoutSecrets(array $values): array
    {
        foreach (config('settings.secrets') as $key) {
            data_set($values, $key, null);
        }

        return $values;
    }

    private function generalTab(): Tab
    {
        return Tab::make('General')
            ->icon(Heroicon::OutlinedHome)
            ->schema([
                TextInput::make('general.site_name')
                    ->label('Site name')
                    ->required()
                    ->maxLength(60),
                TextInput::make('general.tagline')
                    ->label('Tagline')
                    ->helperText('A short line shown under the logo in the footer.')
                    ->maxLength(80),
                Grid::make(2)->schema([
                    FileUpload::make('general.logo')
                        ->label('Logo')
                        ->helperText('PNG, JPG or WebP, ideally wide with a transparent background. Shown about 40px tall. Leave empty to show the site name as text.')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxSize(1024)
                        ->disk(config('media.disk'))
                        ->directory('branding')
                        ->visibility('public'),
                    FileUpload::make('general.favicon')
                        ->label('Browser tab icon (favicon)')
                        ->helperText('A square PNG, at least 512 × 512 pixels.')
                        ->image()
                        ->acceptedFileTypes(['image/png'])
                        ->maxSize(512)
                        ->disk(config('media.disk'))
                        ->directory('branding')
                        ->visibility('public'),
                ]),
                TextInput::make('general.contact_email')
                    ->label('Public contact email')
                    ->helperText('Shown on the site for visitors to contact you.')
                    ->email()
                    ->required(),
                TextInput::make('general.contact_recipient')
                    ->label('Send contact-form messages to')
                    ->helperText('Where messages from the contact form are emailed. Can be different from the public address.')
                    ->email()
                    ->required(),
            ]);
    }

    private function homepageTab(): Tab
    {
        return Tab::make('Homepage')
            ->icon(Heroicon::OutlinedSquares2x2)
            ->schema([
                $this->homepageSection('hero', 'Top banner (hero)', [
                    TextInput::make('homepage.hero.headline')->label('Headline')->maxLength(120),
                    Textarea::make('homepage.hero.text')->label('Text under the headline')->rows(2)->maxLength(250),
                    Grid::make(2)->schema([
                        TextInput::make('homepage.hero.primary_label')->label('Main button text')->maxLength(40),
                        $this->linkInput('homepage.hero.primary_link', 'Main button link'),
                        TextInput::make('homepage.hero.secondary_label')->label('Second link text')->maxLength(40),
                        $this->linkInput('homepage.hero.secondary_link', 'Second link'),
                    ]),
                ], hasTitle: false),
                $this->homepageSection('jobs', 'Job search and latest jobs', [
                    $this->countInput('homepage.jobs.count', 'How many jobs to show'),
                ]),
                $this->homepageSection('guides', 'Featured guides', [], helper: 'Shows articles marked as featured.'),
                $this->homepageSection('creator', 'Publish with us', [
                    Textarea::make('homepage.creator.text')->label('Text')->rows(3)->maxLength(400),
                ]),
                $this->homepageSection('articles', 'Latest articles', [
                    $this->countInput('homepage.articles.count', 'How many articles to show'),
                ]),
                $this->homepageSection('resource', 'Featured resource', [
                    Textarea::make('homepage.resource.text')->label('Text')->rows(3)->maxLength(400),
                    FileUpload::make('homepage.resource.image')
                        ->label('Image')
                        ->image()
                        ->maxSize(2048)
                        ->disk(config('media.disk'))
                        ->directory('homepage')
                        ->visibility('public'),
                    Grid::make(2)->schema([
                        TextInput::make('homepage.resource.link_label')->label('Button text')->maxLength(40),
                        $this->linkInput('homepage.resource.link', 'Button link'),
                    ]),
                ]),
                $this->homepageSection('newsletter', 'Newsletter sign-up', [
                    Textarea::make('homepage.newsletter.text')->label('Text')->rows(2)->maxLength(250),
                ]),
            ]);
    }

    /**
     * @param  array<int, mixed>  $fields
     */
    private function homepageSection(string $key, string $heading, array $fields, bool $hasTitle = true, ?string $helper = null): Section
    {
        $titleField = $hasTitle
            ? [TextInput::make("homepage.{$key}.title")->label('Section title')->maxLength(80)]
            : [];

        return Section::make($heading)
            ->description($helper)
            ->collapsible()
            ->compact()
            ->schema([
                Toggle::make("homepage.{$key}.visible")->label('Show this section'),
                ...$titleField,
                ...$fields,
            ]);
    }

    private function navigationTab(): Tab
    {
        return Tab::make('Navigation')
            ->icon(Heroicon::OutlinedBars3)
            ->schema([
                Section::make('Header menu')
                    ->description('Links across the top of every page. Drag to reorder.')
                    ->schema([
                        Repeater::make('navigation.header')
                            ->hiddenLabel()
                            ->schema($this->menuLinkFields())
                            ->columns(2)
                            ->reorderable()
                            ->maxItems(8)
                            ->addActionLabel('Add menu item')
                            ->defaultItems(0),
                        Grid::make(2)->schema([
                            TextInput::make('navigation.cta_label')->label('Header button text')->maxLength(30),
                            $this->linkInput('navigation.cta_link', 'Header button link'),
                        ]),
                    ]),
                Section::make('Footer links')
                    ->description('Groups of links in the footer, each with a heading.')
                    ->schema([
                        Repeater::make('navigation.footer')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('title')->label('Group heading')->required()->maxLength(40),
                                Repeater::make('links')
                                    ->label('Links')
                                    ->schema($this->menuLinkFields())
                                    ->columns(2)
                                    ->reorderable()
                                    ->addActionLabel('Add link')
                                    ->defaultItems(0),
                            ])
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->maxItems(4)
                            ->addActionLabel('Add link group')
                            ->defaultItems(0),
                    ]),
            ]);
    }

    /**
     * @return array<int, TextInput|Select>
     */
    private function menuLinkFields(): array
    {
        return [
            TextInput::make('label')->label('Text')->required()->maxLength(40),
            $this->linkInput('link', 'Link')->required(),
        ];
    }

    private function socialTab(): Tab
    {
        return Tab::make('Social links')
            ->icon(Heroicon::OutlinedShare)
            ->schema([
                Repeater::make('social.links')
                    ->hiddenLabel()
                    ->schema([
                        Select::make('platform')
                            ->options(config('settings.social_platforms'))
                            ->required(),
                        TextInput::make('url')
                            ->label('Profile address')
                            ->url()
                            ->rule('url:http,https')
                            ->placeholder('https://')
                            ->required(),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->addActionLabel('Add social link')
                    ->defaultItems(0),
            ]);
    }

    private function seoTab(): Tab
    {
        return Tab::make('SEO defaults')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->schema([
                Toggle::make('seo.discourage_search_engines')
                    ->label('Ask search engines not to list the site')
                    ->helperText('Keep this on until the site launches. Turn it off when you are ready to appear in Google.'),
                Textarea::make('seo.default_meta_description')
                    ->label('Default description')
                    ->helperText('Used in search results for pages that don\'t have their own description. Aim for 120–160 characters.')
                    ->rows(3)
                    ->maxLength(200),
                TextInput::make('seo.title_suffix')
                    ->label('Title ending')
                    ->helperText('Added to the end of every page title in browser tabs and search results, e.g. "Remote SEO jobs | Solo Hustle".')
                    ->maxLength(40),
                FileUpload::make('seo.default_share_image')
                    ->label('Default share image')
                    ->helperText('Shown when a page is shared on social media and has no image of its own. 1200 × 630 pixels works best.')
                    ->image()
                    ->maxSize(2048)
                    ->disk(config('media.disk'))
                    ->directory('branding')
                    ->visibility('public'),
            ]);
    }

    private function articleTab(): Tab
    {
        return Tab::make('Article extras')
            ->icon(Heroicon::OutlinedDocumentText)
            ->schema([
                Section::make('Box in the middle of articles')
                    ->description('Shown after the second heading of every article.')
                    ->schema([
                        Textarea::make('article.mid_cta_text')->label('Text')->rows(2)->maxLength(250),
                        Grid::make(2)->schema([
                            TextInput::make('article.mid_cta_label')->label('Button text')->maxLength(40),
                            $this->linkInput('article.mid_cta_link', 'Button link'),
                        ]),
                    ]),
                Textarea::make('article.newsletter_text')
                    ->label('Newsletter box text')
                    ->helperText('Shown at the end of every article.')
                    ->rows(2)
                    ->maxLength(250),
            ]);
    }

    private function jobsTab(): Tab
    {
        return Tab::make('Jobs pages')
            ->icon(Heroicon::OutlinedBriefcase)
            ->schema([
                Section::make('All jobs page (/jobs)')
                    ->schema([
                        TextInput::make('jobs.headline')->label('Headline')->required()->maxLength(100),
                        Textarea::make('jobs.intro')->label('Introduction')->rows(2)->maxLength(300),
                        Textarea::make('jobs.meta_description')->label('Description in search results')->rows(2)->maxLength(160),
                    ]),
                Section::make('Remote jobs page (/jobs/remote)')
                    ->schema([
                        TextInput::make('jobs.remote_headline')->label('Headline')->required()->maxLength(100),
                        Textarea::make('jobs.remote_intro')->label('Introduction')->rows(2)->maxLength(300),
                        Textarea::make('jobs.remote_meta_description')->label('Description in search results')->rows(2)->maxLength(160),
                    ]),
                Text::make('Each job category has its own page too. Edit its introduction under Jobs → Job categories.'),
            ]);
    }

    private function publishTab(): Tab
    {
        return Tab::make('Publish page')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->schema([
                TextInput::make('publish.headline')->label('Headline')->required()->maxLength(100),
                Textarea::make('publish.intro')->label('Introduction')->rows(3)->maxLength(400),
                TextInput::make('publish.cta_label')
                    ->label('Sign-up button text')
                    ->helperText('The button takes visitors to the writer sign-up form.')
                    ->required()
                    ->maxLength(30),
                Repeater::make('publish.benefits')
                    ->label('Why write for us')
                    ->schema([
                        TextInput::make('title')->label('Heading')->required()->maxLength(60),
                        Textarea::make('text')->label('Text')->rows(2)->maxLength(250),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->collapsible()
                    ->reorderable()
                    ->maxItems(6)
                    ->defaultItems(0)
                    ->addActionLabel('Add a reason'),
                Repeater::make('publish.steps')
                    ->label('How it works (steps)')
                    ->schema([
                        TextInput::make('title')->label('Step')->required()->maxLength(60),
                        Textarea::make('text')->label('Text')->rows(2)->maxLength(250),
                    ])
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->collapsible()
                    ->reorderable()
                    ->maxItems(6)
                    ->defaultItems(0)
                    ->addActionLabel('Add a step'),
            ]);
    }

    private function integrationsTab(): Tab
    {
        return Tab::make('Integrations')
            ->icon(Heroicon::OutlinedPuzzlePiece)
            ->schema([
                Section::make('Email sending (Brevo SMTP)')
                    ->description('Leave the server empty and the site uses its built-in email setting. Use "Send test email" at the top to check it works.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('integrations.smtp_host')->label('SMTP server')->placeholder('smtp-relay.brevo.com'),
                        TextInput::make('integrations.smtp_port')->label('Port')->numeric()->placeholder('587'),
                        TextInput::make('integrations.smtp_username')->label('Login'),
                        $this->secretInput('integrations.smtp_password', 'SMTP key / password'),
                        TextInput::make('integrations.mail_from_address')
                            ->label('Send emails from (address)')
                            ->helperText('Must be an address you have verified in Brevo.')
                            ->email(),
                        TextInput::make('integrations.mail_from_name')
                            ->label('Send emails from (name)')
                            ->helperText('Leave empty to use the site name.'),
                    ]),
                Section::make('Newsletter (Brevo)')
                    ->description('Subscribers are always saved on this site. Add your Brevo details to also send them to your Brevo list.')
                    ->columns(2)
                    ->schema([
                        $this->secretInput('integrations.brevo_api_key', 'Brevo API key'),
                        TextInput::make('integrations.brevo_list_id')->label('List ID')->numeric(),
                    ]),
                Section::make('Google')
                    ->columns(2)
                    ->schema([
                        TextInput::make('integrations.ga4_id')
                            ->label('Google Analytics measurement ID')
                            ->helperText('Starts with G-. Only used on the live site.')
                            ->placeholder('G-XXXXXXXXXX')
                            ->regex('/^G-[A-Z0-9]+$/'),
                        TextInput::make('integrations.search_console_code')
                            ->label('Google Search Console verification code')
                            ->helperText('Paste the code, or the whole meta tag Google gives you.')
                            ->dehydrateStateUsing(fn (?string $state): ?string => $this->extractVerificationCode($state)),
                    ]),
            ]);
    }

    private function secretInput(string $key, string $label): TextInput
    {
        return TextInput::make($key)
            ->label($label)
            ->password()
            ->autocomplete('new-password')
            ->helperText(function () use ($key): string {
                $hint = app(Settings::class)->secretHint($key);

                return $hint === null
                    ? 'Not set.'
                    : "Saved ({$hint}). Leave empty to keep it.";
            });
    }

    private function linkInput(string $name, string $label): Select
    {
        return LinkPicker::make($name, $label);
    }

    private function countInput(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->integer()
            ->minValue(1)
            ->maxValue(24)
            ->required();
    }

    /**
     * Google gives a whole <meta> tag; keep just the code so it can be output correctly later.
     */
    private function extractVerificationCode(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (preg_match('/content=["\']([^"\']+)["\']/', $value, $matches) === 1) {
            return $matches[1];
        }

        return trim($value);
    }
}
