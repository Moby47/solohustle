<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/** One IndexNow notification for one changed address. Retried a couple of times if the service is busy. */
class PingIndexNow implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 600];

    public function __construct(public string $url) {}

    public function handle(): void
    {
        $key = setting('ai.indexnow_key');

        if (blank($key)) {
            return;
        }

        Http::timeout(15)->post('https://api.indexnow.org/indexnow', [
            'host' => parse_url(config('app.url'), PHP_URL_HOST),
            'key' => $key,
            'keyLocation' => url("/{$key}.txt"),
            'urlList' => [$this->url],
        ])->throw();
    }
}
