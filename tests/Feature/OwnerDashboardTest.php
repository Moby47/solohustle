<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Filament\Widgets\OwnerStats;
use App\Filament\Widgets\PendingSubmissions;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->admin()->create());
});

test('the dashboard shows quick links and the four counts', function () {
    $this->get('/admin')
        ->assertOk()
        ->assertSee('New job')
        ->assertSee('New article')
        ->assertSee('Articles to review')
        ->assertSee('Jobs to re-check')
        ->assertSee('Unread messages')
        ->assertSee('New subscribers');
});

test('the counts are right', function () {
    Post::factory()->status(PostStatus::Pending)->count(2)->create();
    Job::factory()->create(['last_verified_at' => now()->subDays(45)]);
    ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Hi', 'message' => 'Hello there']);
    Subscriber::create(['email' => 'new@example.com'])->forceFill(['confirmed_at' => now()->subDays(2)])->save();
    Subscriber::create(['email' => 'old@example.com'])->forceFill(['confirmed_at' => now()->subMonth()])->save();

    $stats = collect((fn () => $this->getStats())->call(new OwnerStats))
        ->mapWithKeys(fn ($stat) => [$stat->getLabel() => (int) $stat->getValue()]);

    expect($stats->all())->toBe([
        'Articles to review' => 2,
        'Jobs to re-check' => 1,
        'Unread messages' => 1,
        'New subscribers' => 1,
    ]);
});

test('the review list appears only when something is waiting, oldest first', function () {
    expect(PendingSubmissions::canView())->toBeFalse();

    $newer = Post::factory()->status(PostStatus::Pending)->create(['submitted_at' => now()->subHour()]);
    $older = Post::factory()->status(PostStatus::Pending)->create(['submitted_at' => now()->subDays(3)]);

    expect(PendingSubmissions::canView())->toBeTrue();

    Livewire::test(PendingSubmissions::class)->assertCanSeeTableRecords([$older, $newer], inOrder: true);
});
