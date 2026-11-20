<?php

declare(strict_types=1);

use App\Http\Controllers\JobBoardController;
use App\Http\Controllers\ShowArticle;
use App\Http\Controllers\ShowAuthor;
use App\Http\Controllers\ShowPageOrCategory;
use Illuminate\Support\Facades\Route;

// A lowercase slug that isn't one of the app's own top-level addresses (admin, writer, jobs, …).
$reserved = implode('|', array_map('preg_quote', config('site.reserved_slugs')));
$publicSlug = "(?!(?:{$reserved})(?![a-z0-9-]))[a-z0-9]+(?:-[a-z0-9]+)*";
$slug = '[a-z0-9]+(?:-[a-z0-9]+)*';

Route::view('/', 'home')->name('home');
Route::view('/publish', 'publish')->name('publish');
Route::get('/authors/{slug}', ShowAuthor::class)->where('slug', $slug)->name('authors.show');

Route::get('/jobs', [JobBoardController::class, 'index'])->name('jobs.index');
Route::get('/jobs/remote', [JobBoardController::class, 'remote'])->name('jobs.remote');
Route::get('/jobs/{slug}', [JobBoardController::class, 'category'])->where('slug', $slug)->name('jobs.category');

// Catch-alls for the blog and owner pages. Keep them last so fixed routes win.
Route::get('/{category}/{post}', ShowArticle::class)
    ->where(['category' => $publicSlug, 'post' => $slug])
    ->name('articles.show');

Route::get('/{slug}', ShowPageOrCategory::class)
    ->where('slug', $publicSlug)
    ->name('pages.show');
