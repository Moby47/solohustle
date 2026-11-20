<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;

/** Closes open jobs once their closing date has passed. Runs every night from the scheduler. */
class CloseExpiredJobs extends Command
{
    protected $signature = 'jobs:close-expired';

    protected $description = 'Close open jobs whose closing date has passed';

    public function handle(): int
    {
        // A job closing "on the 5th" stays open all day on the 5th and closes from the 6th.
        $expired = Job::open()
            ->whereNotNull('closes_at')
            ->whereDate('closes_at', '<', today())
            ->get();

        $expired->each(fn (Job $job) => $job->close());

        $this->info("Closed {$expired->count()} expired job(s).");

        return self::SUCCESS;
    }
}
