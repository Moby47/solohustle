<?php

use App\Http\Middleware\HandleRedirects;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global, so it also catches addresses that match no route at all.
        $middleware->append(HandleRedirects::class);

        // Heroku's router (and most hosting load balancers) sits in front of the app; trust it so Laravel sees
        // the real https:// address and visitor IP. Without this, links and signed email links break.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// On Namecheap the web root is public_html, outside the app folder. Read from config so it also works when config is cached.
$app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
    $publicPath = $app['config']->get('app.public_path');

    if (filled($publicPath)) {
        $app->usePublicPath(rtrim($publicPath, '/\\'));
    }
});

return $app;
