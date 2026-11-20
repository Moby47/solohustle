<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** A permanent (301) redirect from an old address on this site to a new one. */
#[Fillable(['from_path', 'to_path'])]
class Redirect extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect) {
            $redirect->from_path = static::normalizePath($redirect->from_path);
        });
    }

    /**
     * "/About/" and "about" both become "/About": one leading slash, no trailing slash, no query string.
     */
    public static function normalizePath(string $path): string
    {
        $path = (string) parse_url(trim($path), PHP_URL_PATH);

        return '/'.trim($path, '/');
    }

    /**
     * Record that content moved, keeping old links working without chains or loops.
     */
    public static function recordMove(string $from, string $to): void
    {
        $from = static::normalizePath($from);
        $to = static::normalizePath($to);

        if ($from === $to) {
            return;
        }

        // The new address is live again, so nothing should redirect away from it.
        static::where('from_path', $to)->delete();

        // Anything that pointed at the old address now goes straight to the new one.
        static::where('to_path', $from)->update(['to_path' => $to]);

        static::updateOrCreate(['from_path' => $from], ['to_path' => $to]);
    }
}
