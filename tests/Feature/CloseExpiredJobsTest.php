<?php

declare(strict_types=1);

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('closes open jobs whose closing date has passed, and nothing else', function () {
    $expired = Job::factory()->create(['closes_at' => today()->subDay()]);
    $closesToday = Job::factory()->create(['closes_at' => today()]);
    $closesLater = Job::factory()->create(['closes_at' => today()->addWeek()]);
    $noDate = Job::factory()->create(['closes_at' => null]);
    $alreadyClosed = Job::factory()->closed()->create(['closes_at' => today()->subMonth(), 'closed_at' => now()->subMonth()]);

    $this->artisan('jobs:close-expired')
        ->expectsOutput('Closed 1 expired job(s).')
        ->assertSuccessful();

    expect($expired->fresh())->status->toBe(JobStatus::Closed)->closed_at->not->toBeNull()
        ->and($closesToday->fresh()->status)->toBe(JobStatus::Open)
        ->and($closesLater->fresh()->status)->toBe(JobStatus::Open)
        ->and($noDate->fresh()->status)->toBe(JobStatus::Open)
        ->and($alreadyClosed->fresh()->closed_at->isSameDay(now()->subMonth()))->toBeTrue();
});

test('runs every night', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'jobs:close-expired'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('15 0 * * *');
});

test('"needs re-check" means open and not checked for over 30 days', function () {
    $stale = Job::factory()->create(['last_verified_at' => now()->subDays(31)]);
    $fresh = Job::factory()->create(['last_verified_at' => now()->subDays(29)]);
    $staleButClosed = Job::factory()->closed()->create(['last_verified_at' => now()->subDays(90)]);

    expect(Job::needsRecheck()->pluck('id')->all())->toBe([$stale->id])
        ->and($stale->isDueForRecheck())->toBeTrue()
        ->and($fresh->isDueForRecheck())->toBeFalse()
        ->and($staleButClosed->isDueForRecheck())->toBeFalse();
});
