<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Shortly after midnight, so jobs whose closing date was yesterday close first thing.
Schedule::command('jobs:close-expired')->dailyAt('00:15')->withoutOverlapping();
