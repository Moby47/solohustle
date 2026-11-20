<?php

declare(strict_types=1);

use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\MailSettings;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function settings(): Settings
{
    return app(Settings::class);
}

test('defaults are used until something is saved', function () {
    expect(setting('general.site_name'))->toBe('Solo Hustle')
        ->and(setting('general.contact_email'))->toBe('solohustlehub@gmail.com')
        ->and(setting('seo.discourage_search_engines'))->toBeTrue()
        ->and(setting('social.links'))->toHaveCount(4);
});

test('saved values override defaults and the cache is cleared on save', function () {
    expect(setting('general.site_name'))->toBe('Solo Hustle');

    settings()->save(['general' => ['site_name' => 'Hustle HQ']]);

    expect(setting('general.site_name'))->toBe('Hustle HQ')
        ->and(setting('general.tagline'))->toBe('Work. Write. Build.');
});

test('saved lists replace the default list instead of merging into it', function () {
    settings()->save(['social' => ['links' => [['platform' => 'youtube', 'url' => 'https://youtube.com/@sh']]]]);

    expect(setting('social.links'))->toEqual([['platform' => 'youtube', 'url' => 'https://youtube.com/@sh']]);
});

test('secrets are stored encrypted and a blank value keeps the saved one', function () {
    settings()->save(['integrations' => ['smtp_host' => 'smtp.example.com', 'smtp_password' => 'super-secret-key']]);

    $stored = Setting::where('key', 'integrations')->first()->value['smtp_password'];

    expect($stored)->not->toBe('super-secret-key')
        ->and(settings()->secret('integrations.smtp_password'))->toBe('super-secret-key')
        ->and(settings()->secretHint('integrations.smtp_password'))->toBe('ends in ••ey');

    settings()->save(['integrations' => ['smtp_host' => 'smtp.example.com', 'smtp_password' => null]]);

    expect(settings()->secret('integrations.smtp_password'))->toBe('super-secret-key');
});

test('saved SMTP details become the mail configuration', function () {
    settings()->save(['integrations' => [
        'smtp_host' => 'smtp-relay.brevo.com',
        'smtp_port' => 587,
        'smtp_username' => 'me@example.com',
        'smtp_password' => 'brevo-key',
        'mail_from_address' => 'hello@solohustle.test',
        'mail_from_name' => null,
    ]]);

    app(MailSettings::class)->apply();

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp-relay.brevo.com')
        ->and(config('mail.mailers.smtp.password'))->toBe('brevo-key')
        ->and(config('mail.from.address'))->toBe('hello@solohustle.test')
        ->and(config('mail.from.name'))->toBe('Solo Hustle');
});

test('without SMTP details the .env mailer is kept', function () {
    app(MailSettings::class)->apply();

    expect(config('mail.default'))->toBe('array');
});

describe('settings page', function () {
    beforeEach(function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    });

    test('only admins can open it', function () {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/settings')->assertOk();
        $this->actingAs(User::factory()->create())->get('/admin/settings')->assertForbidden();
    });

    test('never sends saved secrets back to the browser', function () {
        settings()->save(['integrations' => ['smtp_password' => 'super-secret-key']]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->assertSet('data.integrations.smtp_password', null)
            ->assertDontSee('super-secret-key');
    });

    test('saves the form', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'general.site_name' => 'Hustle HQ',
                'navigation.header' => [['label' => 'Jobs', 'link' => '/jobs']],
                'integrations.search_console_code' => '<meta name="google-site-verification" content="abc123" />',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Settings saved');

        expect(setting('general.site_name'))->toBe('Hustle HQ')
            ->and(setting('navigation.header'))->toEqual([['label' => 'Jobs', 'link' => '/jobs']])
            ->and(setting('integrations.search_console_code'))->toBe('abc123');
    });

    test('rejects links that are not a site page or a web address', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->fillForm(['navigation.cta_link' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasFormErrors(['navigation.cta_link']);
    });

    test('sends a test email', function () {
        Event::fake([MessageSent::class]);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)
            ->callAction('sendTestEmail', ['email' => 'owner@example.com'])
            ->assertNotified('Test email sent to owner@example.com');

        Event::assertDispatched(MessageSent::class);
    });
});

test('the public site uses the saved site name', function () {
    settings()->save(['general' => ['site_name' => 'Hustle HQ']]);

    $this->withoutVite()->get('/')->assertSee('<title>Hustle HQ</title>', false);
});
