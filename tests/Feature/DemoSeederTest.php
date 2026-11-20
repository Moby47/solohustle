<?php

declare(strict_types=1);

use App\Enums\JobStatus;
use App\Enums\PostStatus;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\Post;
use App\Models\Subscriber;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media_local');
});

test('creates a full set of demo content with local placeholder images', function () {
    $this->seed(DemoSeeder::class);

    expect(User::where('role', 'writer')->count())->toBe(4)
        ->and(User::where('email', 'admin@solohustle.test')->first()->isAdmin())->toBeTrue()
        ->and(Post::count())->toBe(16)
        ->and(Post::where('status', PostStatus::Published)->count())->toBe(10)
        ->and(Post::where('status', PostStatus::Pending)->count())->toBe(2)
        ->and(Post::where('is_featured', true)->count())->toBe(3)
        ->and(Job::count())->toBe(30)
        ->and(Job::where('status', JobStatus::Closed)->count())->toBe(5)
        ->and(Job::needsRecheck()->count())->toBeGreaterThan(0)
        ->and(Job::whereNotNull('closes_at')->count())->toBeGreaterThan(0)
        ->and(Subscriber::active()->count())->toBe(3)
        ->and(ContactMessage::unread()->count())->toBe(2);

    $cover = Post::whereNotNull('cover_image')->first()->cover_image;
    Storage::disk('media_local')->assertExists([$cover, str_replace('.webp', '-480.webp', $cover)]);
    expect(Post::whereNotNull('cover_image')->first()->cover_image)->toStartWith('covers/');
});

test('runs only once and never in production', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Post::count())->toBe(16);
});

test('every demo page renders', function () {
    $this->withoutVite();
    $this->seed(DemoSeeder::class);

    $this->get('/')->assertOk();
    $this->get('/jobs')->assertOk();

    Post::published()->with('category')->get()->each(fn (Post $post) => $this->get($post->path())->assertOk());
    Job::with('jobCategory')->get()->each(fn (Job $job) => $this->get($job->path())->assertOk());
    User::where('role', 'writer')->get()->filter->hasAuthorPage()->each(fn (User $writer) => $this->get('/authors/'.$writer->slug)->assertOk());
});
