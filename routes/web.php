<?php

declare(strict_types=1);

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobBoardController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShowArticle;
use App\Http\Controllers\ShowAuthor;
use App\Http\Controllers\ShowPageOrCategory;
use App\Support\LlmsTxt;
use App\Support\SitemapBuilder;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

// A lowercase slug that isn't one of the app's own top-level addresses (admin, writer, jobs, …).
$reserved = implode('|', array_map('preg_quote', config('site.reserved_slugs')));
$publicSlug = "(?!(?:{$reserved})(?![a-z0-9-]))[a-z0-9]+(?:-[a-z0-9]+)*";
$slug = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::get('/', HomeController::class)->name('home');
Route::view('/publish', 'publish')->name('publish');
Route::get('/search', SearchController::class)->name('search');

Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::get('/llms.txt', function (LlmsTxt $llms) {
    abort_unless(setting('ai.llms_txt'), 404);

    return response($llms->content(), 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
})->name('llms');

// The IndexNow key file proves to search engines that the pings really come from this site.
Route::get('/{key}.txt', function (string $key) {
    abort_unless(setting('ai.indexnow_enabled') && hash_equals((string) setting('ai.indexnow_key'), $key), 404);

    return response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->where('key', '[a-z0-9]{32}')->name('indexnow.key');

// Normally the web server serves the generated public/sitemap.xml; this only answers before the first generation.
Route::get('/sitemap.xml', fn (SitemapBuilder $sitemap) => response($sitemap->build(), 200, ['Content-Type' => 'application/xml']))
    ->name('sitemap');

// Public forms: honeypot + per-visitor rate limit.
Route::middleware([ProtectAgainstSpam::class, 'throttle:public-forms'])->group(function () {
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
    Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
});

// Signed links from emails; the controller checks the signature so it can show a friendly page.
Route::get('/newsletter/confirm/{subscriber}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/authors/{slug}', ShowAuthor::class)->where('slug', $slug)->name('authors.show');

Route::get('/jobs', [JobBoardController::class, 'index'])->name('jobs.index');
Route::get('/jobs/remote', [JobBoardController::class, 'remote'])->name('jobs.remote');
Route::get('/jobs/{slug}', [JobBoardController::class, 'category'])->where('slug', $slug)->name('jobs.category');
Route::get('/jobs/{category}/{job}', [JobBoardController::class, 'show'])
    ->where(['category' => $slug, 'job' => $slug])
    ->name('jobs.show');

// Catch-alls for the blog and owner pages. Keep them last so fixed routes win.
Route::get('/{category}/{post}', ShowArticle::class)
    ->where(['category' => $publicSlug, 'post' => $slug])
    ->name('articles.show');

Route::get('/{slug}', ShowPageOrCategory::class)
    ->where('slug', $publicSlug)
    ->name('pages.show');
