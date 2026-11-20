<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/** Site settings: config defaults overlaid with what the owner saved, cached until the next save. */
final class Settings
{
    private const CACHE_KEY = 'settings';

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->resolved ??= $this->merge(config('settings.defaults'), $this->stored());
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * The decrypted value of a secret setting, or null when none is saved.
     */
    public function secret(string $key): ?string
    {
        $encrypted = $this->get($key);

        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * A safe hint for the dashboard, e.g. "ends in ••ab", so the owner can tell a secret is saved.
     */
    public function secretHint(string $key): ?string
    {
        $secret = $this->secret($key);

        return $secret === null ? null : 'ends in ••'.Str::substr($secret, -2);
    }

    /**
     * Save whole tabs. A blank secret keeps the one already saved.
     *
     * @param  array<string, array<string, mixed>>  $tabs
     */
    public function save(array $tabs): void
    {
        foreach (config('settings.secrets') as $key) {
            $tab = Str::before($key, '.');

            if (! array_key_exists($tab, $tabs)) {
                continue;
            }

            $new = data_get($tabs, $key);
            data_set($tabs, $key, filled($new) ? Crypt::encryptString($new) : $this->get($key));
        }

        foreach ($tabs as $tab => $values) {
            Setting::updateOrCreate(['key' => $tab], ['value' => $values]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        LlmsTxt::forget();
        PageCache::flush();
        $this->resolved = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        try {
            return Cache::rememberForever(
                self::CACHE_KEY,
                fn (): array => Setting::query()->pluck('value', 'key')->all(),
            );
        } catch (QueryException) {
            // Before the first migration (fresh install) there is no table yet; run on defaults.
            return [];
        }
    }

    /**
     * Saved values win. Keyed groups merge so new defaults still appear; lists (menus, links) replace wholesale.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private function merge(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            $bothKeyedGroups = is_array($value) && ! array_is_list($value)
                && is_array($defaults[$key] ?? null) && ! array_is_list($defaults[$key]);

            $defaults[$key] = $bothKeyedGroups ? $this->merge($defaults[$key], $value) : $value;
        }

        return $defaults;
    }
}
