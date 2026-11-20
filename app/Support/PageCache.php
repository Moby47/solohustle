<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page cache for visitors. Pages are stored per address and all thrown away at once (by bumping a version
 * number) whenever content or settings change, so visitors never see stale pages for long.
 */
final class PageCache
{
    private const VERSION_KEY = 'page-cache:version';

    /** Even without changes, pages refresh at least this often (e.g. "posted 3 days ago" stays right). */
    private const TTL_MINUTES = 60 * 6;

    /** Stand-in for each visitor's own security token inside cached HTML. */
    private const TOKEN_PLACEHOLDER = '__PAGE_CACHE_CSRF__';

    public static function flush(): void
    {
        // A new version makes every stored page unreachable; old copies simply expire.
        Cache::forever(self::VERSION_KEY, (string) microtime(true));
    }

    public function shouldCache(Request $request): bool
    {
        if (! config('site.page_cache') || ! $request->isMethod('GET') || $request->user() !== null) {
            return false;
        }

        // A thank-you message, form errors or typed values must reach this visitor, so never serve or store the cache then.
        $session = $request->hasSession() ? $request->session() : null;

        return $session === null
            || ($session->get('_flash.old', []) === [] && ! $session->has('errors') && ! $session->has('_old_input'));
    }

    public function get(Request $request): ?Response
    {
        $cached = Cache::get($this->key($request));

        if ($cached === null) {
            return null;
        }

        return response(str_replace(self::TOKEN_PLACEHOLDER, csrf_token(), $cached['content']), 200, [
            'Content-Type' => $cached['type'],
            'X-Page-Cache' => 'hit',
        ]);
    }

    public function put(Request $request, Response $response): void
    {
        $content = (string) $response->getContent();

        Cache::put($this->key($request), [
            'content' => str_replace(csrf_token(), self::TOKEN_PLACEHOLDER, $content),
            'type' => $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
        ], now()->addMinutes(self::TTL_MINUTES));

        $response->headers->set('X-Page-Cache', 'miss');
    }

    public function isCacheable(Response $response): bool
    {
        return $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }

    private function key(Request $request): string
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn (): string => (string) microtime(true));

        return 'page-cache:'.$version.':'.sha1($request->fullUrl());
    }
}
