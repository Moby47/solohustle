<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends visitors from an old address to its new one, but only when the old address would otherwise be "not found". */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404 || ! $request->isMethodSafe()) {
            return $response;
        }

        try {
            $redirect = Redirect::where('from_path', Redirect::normalizePath($request->getPathInfo()))->first();
        } catch (QueryException) {
            // Fresh install before migrations: just show the 404.
            return $response;
        }

        if ($redirect === null) {
            return $response;
        }

        $redirect->increment('hits');

        return redirect()->to($this->target($redirect->to_path, $request), 301);
    }

    private function target(string $toPath, Request $request): string
    {
        $query = $request->getQueryString();

        if ($query === null || str_contains($toPath, '?')) {
            return $toPath;
        }

        return $toPath.'?'.$query;
    }
}
