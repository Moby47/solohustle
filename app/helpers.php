<?php

declare(strict_types=1);

use App\Support\Settings;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /**
     * Read a site setting by dotted key, e.g. setting('general.site_name').
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('media_url')) {
    /**
     * Public URL of an uploaded file on the media disk, or null when there is no file.
     */
    function media_url(?string $path): ?string
    {
        return filled($path) ? Storage::disk(config('media.disk'))->url($path) : null;
    }
}
