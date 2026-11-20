<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\PageCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Serves public pages from the page cache for visitors, and stores fresh copies. */
class CachePublicPages
{
    public function __construct(private readonly PageCache $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cache->shouldCache($request)) {
            return $next($request);
        }

        if ($cached = $this->cache->get($request)) {
            return $cached;
        }

        $response = $next($request);

        if ($this->cache->isCacheable($response)) {
            $this->cache->put($request, $response);
        }

        return $response;
    }
}
