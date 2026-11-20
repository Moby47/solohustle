<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Shortly after midnight, so jobs whose closing date was yesterday close first thing.
Schedule::command('jobs:close-expired')->dailyAt('00:15')->withoutOverlapping();

// After the nightly job closing, so closed jobs drop out of the sitemap the same night.
Schedule::command('sitemap:generate')->dailyAt('00:30')->withoutOverlapping();
