<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The double opt-in email: one click to confirm the subscription. */
class ConfirmNewsletterSubscription extends Notification implements ShouldQueue
{
    use Queueable;

    /** If the record was deleted before the email went out, drop the email instead of failing. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Subscriber $subscriber) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = setting('general.site_name');

        return (new MailMessage)
            ->subject("Please confirm your subscription to {$siteName}")
            ->greeting('One last step')
            ->line("Click the button below to start getting new jobs and guides from {$siteName} by email.")
            ->action('Yes, subscribe me', $this->subscriber->confirmUrl())
            ->line('This link works for '.Subscriber::CONFIRM_LINK_DAYS.' days. If you didn\'t ask to subscribe, just ignore this email and you won\'t hear from us.');
    }
}
