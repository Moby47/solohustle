<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\PingIndexNow;
use Illuminate\Support\Str;

/** Tells Bing, Yandex and other IndexNow search engines straight away when a page is added, changed or closed. */
final class IndexNow
{
    public function __construct(private readonly Settings $settings) {}

    public function isActive(): bool
    {
        return app()->isProduction()
            && (bool) $this->settings->get('ai.indexnow_enabled')
            && filled($this->settings->get('ai.indexnow_key'));
    }

    public function submit(string $url): void
    {
        if ($this->isActive()) {
            PingIndexNow::dispatch($url);
        }
    }

    public function keyFileUrl(): ?string
    {
        $key = $this->settings->get('ai.indexnow_key');

        return $key ? url("/{$key}.txt") : null;
    }

    public static function newKey(): string
    {
        return Str::lower(Str::random(32));
    }
}
