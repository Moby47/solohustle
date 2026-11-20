<?php

declare(strict_types=1);

namespace App\Mail;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Forwards a contact-form message to the owner; replying goes straight to the visitor. */
class ContactMessageReceived extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /** If the record was deleted before the email went out, drop the email instead of failing. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
            subject: 'New message: '.$this->contactMessage->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-message',
            with: [
                'inboxUrl' => ContactMessageResource::getUrl('view', ['record' => $this->contactMessage], panel: 'admin'),
            ],
        );
    }
}
