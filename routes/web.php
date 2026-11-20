<?php

declare(strict_types=1);

use App\Http\Controllers\ShowPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Catch-all for owner pages (and blog categories from C1). Keep it last so fixed routes win.
Route::get('/{slug}', ShowPage::class)
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('pages.show');
