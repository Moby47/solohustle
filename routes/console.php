<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Emails and Brevo/IndexNow updates wait in the database queue; this sends them. Runs on Heroku's worker
// (schedule:work) or a cPanel cron (schedule:run). Stops before the next minute so runs never pile up.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Scheduled articles: when their publish time arrives, refresh caches and tell search engines.
Schedule::command('posts:announce-scheduled')->everyFiveMinutes()->withoutOverlapping();

// Shortly after midnight, so jobs whose closing date was yesterday close first thing.
Schedule::command('jobs:close-expired')->dailyAt('00:15')->withoutOverlapping();

// After the nightly job closing, so closed jobs drop out of the sitemap the same night.
Schedule::command('sitemap:generate')->dailyAt('00:30')->withoutOverlapping();

// Housekeeping: old failed jobs, and expired cache rows (the database cache never removes them by itself).
Schedule::command('queue:prune-failed --hours=168')->weekly();
Schedule::command('cache:prune-expired')->dailyAt('03:00');

// A heartbeat, so the dashboard can show that the scheduler is really running.
Schedule::call(fn () => Cache::forever('scheduler:last_run', now()->toIso8601String()))
    ->everyMinute()
    ->name('scheduler-heartbeat');
