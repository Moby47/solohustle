<?php

declare(strict_types=1);

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\User;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Mail::fake();
    config(['honeypot.enabled' => false]);
    Page::create(['title' => 'Contact us', 'slug' => 'contact', 'body' => '<p>Say hello.</p>', 'is_published' => true]);
});

function contactData(array $overrides = []): array
{
    return ['name' => 'Ada', 'email' => 'ada@example.com', 'subject' => 'Partnership', 'message' => 'We would love to work with you.', ...$overrides];
}

test('the Contact page shows the form', function () {
    $this->get('/contact')->assertOk()->assertSee('Send us a message')->assertSee('action="'.route('contact.store').'"', false);
});

test('a message is saved, emailed to the chosen address with reply-to, and thanked', function () {
    app(Settings::class)->save(['general' => ['contact_recipient' => 'inbox@solohustle.test']]);

    $this->post('/contact', contactData())
        ->assertRedirect(url('/contact').'#contact-form')
        ->assertSessionHas('contact_status');

    $message = ContactMessage::sole();
    expect($message->read_at)->toBeNull();

    Mail::assertQueued(ContactMessageReceived::class, fn (ContactMessageReceived $mail) => $mail->hasTo('inbox@solohustle.test')
        && $mail->hasReplyTo('ada@example.com'));
});

test('missing or bad fields come back with friendly messages', function () {
    $this->from('/contact')->post('/contact', contactData(['email' => 'not-an-email', 'message' => 'Hi']))
        ->assertRedirect('/contact')
        ->assertSessionHasErrors(['email' => 'That email address doesn\'t look right.', 'message']);

    expect(ContactMessage::count())->toBe(0);
});

test('the honeypot stops bots', function () {
    config(['honeypot.enabled' => true]);

    $this->post('/contact', [...contactData(), 'my_name' => 'bot']);

    expect(ContactMessage::count())->toBe(0);
});

test('too many messages from one visitor are slowed down', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/contact', contactData());
    }

    $this->post('/contact', contactData())->assertTooManyRequests();
});

test('the email renders with a link to the Inbox', function () {
    $message = ContactMessage::create(contactData());

    expect((new ContactMessageReceived($message))->render())->toContain('We would love to work with you.')->toContain('/admin/inbox/');
});

describe('inbox', function () {
    beforeEach(function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    });

    test('opening a message marks it read and offers a reply link', function () {
        $message = ContactMessage::create(contactData());

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertSee('We would love to work with you.')
            ->assertActionHasUrl('reply', 'mailto:ada@example.com?subject=Re%3A%20Partnership');

        expect($message->fresh()->isRead())->toBeTrue();
    });

    test('unread tab, mark unread and bulk mark read', function () {
        $unread = ContactMessage::create(contactData());
        $read = ContactMessage::create(contactData(['subject' => 'Old']));
        $read->markRead();

        Livewire::test(ListContactMessages::class)
            ->set('activeTab', 'unread')
            ->assertCanSeeTableRecords([$unread])
            ->assertCanNotSeeTableRecords([$read]);

        Livewire::test(ListContactMessages::class)->callTableAction('markUnread', $read);
        expect($read->fresh()->isRead())->toBeFalse();

        Livewire::test(ListContactMessages::class)->callTableBulkAction('markRead', [$unread, $read]);
        expect(ContactMessage::unread()->count())->toBe(0);
    });
});
