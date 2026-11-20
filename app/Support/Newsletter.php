<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\SyncSubscriberToBrevo;
use App\Models\Subscriber;
use App\Notifications\ConfirmNewsletterSubscription;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Newsletter sign-up, confirmation and unsubscribe. Subscribers are always kept here; Brevo is optional. */
final class Newsletter
{
    public function __construct(private readonly Brevo $brevo) {}

    /**
     * Starts (or restarts) a subscription and sends the confirmation email. Already-active subscribers are left alone.
     */
    public function subscribe(string $email, ?string $name, ?string $source): Subscriber
    {
        $subscriber = Subscriber::firstOrNew(['email' => mb_strtolower(trim($email))]);

        if ($subscriber->exists && $subscriber->isActive()) {
            return $subscriber;
        }

        $subscriber->fill(['name' => $name ?: $subscriber->name, 'source' => $subscriber->source ?? $source]);
        $subscriber->forceFill(['confirmed_at' => null, 'unsubscribed_at' => null])->save();

        try {
            Notification::route('mail', $subscriber->email)->notify(new ConfirmNewsletterSubscription($subscriber));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $subscriber;
    }

    public function confirm(Subscriber $subscriber): void
    {
        if ($subscriber->unsubscribed_at !== null || $subscriber->confirmed_at !== null) {
            return;
        }

        $subscriber->forceFill(['confirmed_at' => now()])->save();

        $this->syncToBrevo($subscriber, subscribe: true);
    }

    public function unsubscribe(Subscriber $subscriber): void
    {
        if ($subscriber->unsubscribed_at !== null) {
            return;
        }

        $subscriber->forceFill(['unsubscribed_at' => now()])->save();

        $this->syncToBrevo($subscriber, subscribe: false);
    }

    /**
     * Queue a Brevo update for every active subscriber. Returns how many were queued.
     */
    public function syncAllToBrevo(): int
    {
        $count = 0;

        Subscriber::active()->each(function (Subscriber $subscriber) use (&$count): void {
            $this->syncToBrevo($subscriber, subscribe: true);
            $count++;
        });

        return $count;
    }

    public function syncToBrevo(Subscriber $subscriber, bool $subscribe): void
    {
        if (! $this->brevo->isConfigured()) {
            return;
        }

        SyncSubscriberToBrevo::dispatch($subscriber->email, $subscriber->name, $subscribe);
    }
}
