<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Subscriber;
use App\Support\Brevo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Adds a confirmed subscriber to the Brevo list, or removes someone who unsubscribed. Retries if Brevo is briefly down. */
class SyncSubscriberToBrevo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 600];

    /**
     * Takes the email rather than the model, so removals still work after a subscriber is deleted here.
     */
    public function __construct(
        public string $email,
        public ?string $name,
        public bool $subscribe,
    ) {}

    public function handle(Brevo $brevo): void
    {
        if (! $brevo->isConfigured()) {
            return;
        }

        if (! $this->subscribe) {
            $brevo->unsubscribe($this->email);

            return;
        }

        $brevo->subscribe($this->email, $this->name);

        Subscriber::where('email', $this->email)->update(['brevo_synced_at' => now()]);
    }
}
