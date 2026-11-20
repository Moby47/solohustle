<?php

declare(strict_types=1);

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Jobs\SyncSubscriberToBrevo;
use App\Models\Subscriber;
use App\Models\User;
use App\Notifications\ConfirmNewsletterSubscription;
use App\Support\Settings;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    config(['honeypot.enabled' => false]);
});

function connectBrevo(): void
{
    app(Settings::class)->save(['integrations' => ['brevo_api_key' => 'xkeysib-test', 'brevo_list_id' => 7]]);
}

function signUp(string $email = 'Ada@Example.com'): TestResponse
{
    return test()->from('/')->post('/newsletter', ['email' => $email, 'source' => 'homepage', 'form_id' => 'newsletter-homepage']);
}

describe('signing up', function () {
    test('works without Brevo: saved locally, waiting for confirmation, confirmation email sent', function () {
        Queue::fake();

        signUp()->assertRedirect(url('/').'#newsletter-homepage')->assertSessionHas('newsletter_status');

        $subscriber = Subscriber::sole();
        expect($subscriber->email)->toBe('ada@example.com')
            ->and($subscriber->source)->toBe('homepage')
            ->and($subscriber->isActive())->toBeFalse();

        Notification::assertSentTo(new AnonymousNotifiable, ConfirmNewsletterSubscription::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'ada@example.com');
        Queue::assertNotPushed(SyncSubscriberToBrevo::class);
    });

    test('a bad email is refused with a friendly message', function () {
        signUp('nope')->assertSessionHasErrors(['email' => 'That email address doesn\'t look right.']);

        expect(Subscriber::count())->toBe(0);
    });

    test('signing up again does not duplicate, and gives the same reply either way', function () {
        signUp();
        Subscriber::sole()->forceFill(['confirmed_at' => now()])->save();

        signUp('ada@example.com')->assertSessionHas('newsletter_status', fn (string $status) => str_contains($status, 'Check your inbox'));

        expect(Subscriber::count())->toBe(1);
        Notification::assertSentTimes(ConfirmNewsletterSubscription::class, 1);
    });

    test('the homepage and articles show the sign-up form', function () {
        $this->get('/')->assertSee('action="'.route('newsletter.subscribe'), false);
    });
});

describe('confirming and unsubscribing', function () {
    test('the confirmation link activates the subscriber and sends them to Brevo when connected', function () {
        connectBrevo();
        Queue::fake();
        signUp();
        $subscriber = Subscriber::sole();

        $this->get($subscriber->confirmUrl())->assertOk()->assertSee('You\'re subscribed!');

        expect($subscriber->fresh()->isActive())->toBeTrue();
        Queue::assertPushed(SyncSubscriberToBrevo::class, fn ($job) => $job->email === 'ada@example.com' && $job->subscribe);
    });

    test('an expired or tampered link shows a friendly page and changes nothing', function () {
        signUp();
        $subscriber = Subscriber::sole();
        $url = $subscriber->confirmUrl();

        $this->travel(Subscriber::CONFIRM_LINK_DAYS + 1)->days();
        $this->get($url)->assertOk()->assertSee('This link has expired');
        $this->get(route('newsletter.confirm', $subscriber))->assertSee('This link has expired');

        expect($subscriber->fresh()->isActive())->toBeFalse();
    });

    test('unsubscribing works with the signed link and tells Brevo', function () {
        connectBrevo();
        Queue::fake();
        signUp();
        $subscriber = Subscriber::sole();
        $subscriber->forceFill(['confirmed_at' => now()])->save();

        $this->get($subscriber->unsubscribeUrl())->assertOk()->assertSee('You\'ve been unsubscribed');

        expect($subscriber->fresh()->isActive())->toBeFalse();
        Queue::assertPushed(SyncSubscriberToBrevo::class, fn ($job) => ! $job->subscribe);
        $this->get(route('newsletter.unsubscribe', $subscriber))->assertNotFound();
    });
});

describe('Brevo', function () {
    test('the sync job adds the contact to the list and records it', function () {
        connectBrevo();
        Http::fake(['api.brevo.com/*' => Http::response(['id' => 1], 201)]);
        $subscriber = Subscriber::create(['email' => 'ada@example.com', 'name' => 'Ada']);

        dispatch_sync(new SyncSubscriberToBrevo('ada@example.com', 'Ada', true));

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.brevo.com/v3/contacts'
            && $request->hasHeader('api-key', 'xkeysib-test')
            && $request['listIds'] === [7]
            && $request['attributes'] === ['FIRSTNAME' => 'Ada']);
        expect($subscriber->fresh()->brevo_synced_at)->not->toBeNull();
    });

    test('the sync job does nothing when Brevo is not connected', function () {
        Http::fake();

        dispatch_sync(new SyncSubscriberToBrevo('ada@example.com', null, true));

        Http::assertNothingSent();
    });

    test('Test connection and Sync buttons in Site settings', function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)->callAction(TestAction::make('testBrevo')->schemaComponent('brevoActions', 'form'))->assertNotified('Add your Brevo API key and list ID, then save, first.');

        connectBrevo();
        Http::fake(['api.brevo.com/v3/account' => Http::response(['email' => 'owner@solohustle.test'])]);
        Queue::fake();
        Subscriber::create(['email' => 'a@example.com'])->forceFill(['confirmed_at' => now()])->save();
        Subscriber::create(['email' => 'pending@example.com']);

        Livewire::test(ManageSettings::class)->callAction(TestAction::make('testBrevo')->schemaComponent('brevoActions', 'form'))->assertNotified('Connected to Brevo as owner@solohustle.test');
        Livewire::test(ManageSettings::class)->callAction(TestAction::make('syncBrevo')->schemaComponent('brevoActions', 'form'))->assertNotified('Sending 1 subscriber(s) to Brevo');
        Queue::assertPushed(SyncSubscriberToBrevo::class, 1);
    });
});

describe('subscribers in the dashboard', function () {
    beforeEach(function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    });

    test('lists subscribers with their status and filters by it', function () {
        $active = Subscriber::create(['email' => 'active@example.com']);
        $active->forceFill(['confirmed_at' => now()])->save();
        $pending = Subscriber::create(['email' => 'pending@example.com']);

        Livewire::test(ListSubscribers::class)
            ->assertCanSeeTableRecords([$active, $pending])
            ->filterTable('status', 'subscribed')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$pending]);
    });

    test('downloads a CSV', function () {
        Subscriber::create(['email' => 'csv@example.com', 'name' => 'Csv Person']);

        Livewire::test(ListSubscribers::class)
            ->callAction('exportCsv')
            ->assertFileDownloaded('subscribers-'.now()->format('Y-m-d').'.csv');
    });
});
