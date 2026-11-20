<?php

declare(strict_types=1);

namespace App\Support;

/** Helpers for menu links saved in Settings (either "/path" or a full web address). */
final class NavLink
{
    public static function href(string $link): string
    {
        return self::isExternal($link) ? $link : url($link);
    }

    public static function isExternal(string $link): bool
    {
        return preg_match('#^https?://#i', $link) === 1;
    }

    /**
     * True on the linked page and anything below it (e.g. "/career" is active on "/career/some-article").
     */
    public static function isActive(string $link): bool
    {
        if (self::isExternal($link)) {
            return false;
        }

        $path = trim($link, '/');

        if ($path === '') {
            return request()->is('/');
        }

        return request()->is($path) || request()->is($path.'/*');
    }
}
