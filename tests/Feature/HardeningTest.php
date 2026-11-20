<?php

declare(strict_types=1);

use App\Enums\JobStatus;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Jobs\Pages\CreateJob;
use App\Filament\Resources\Jobs\Pages\EditJob;
use App\Jobs\PingIndexNow;
use App\Livewire\JobDirectory;
use App\Mail\ContactMessageReceived;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Post;
use App\Models\Region;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\ImageProcessor;
use App\Support\PageCache;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('scheduler', function () {
    test('runs the email queue every minute and all the housekeeping', function () {
        $commands = collect(app(Schedule::class)->events())
            ->mapWithKeys(fn ($event) => [trim(str_replace(["'".PHP_BINARY."'", '"'.PHP_BINARY.'"', PHP_BINARY, "'artisan'", 'artisan'], '', (string) $event->command)) => $event->expression]);

        expect($commands->first(fn ($expression, $command) => str_contains($command, 'queue:work --stop-when-empty')))->toBe('* * * * *')
            ->and($commands->first(fn ($expression, $command) => str_contains($command, 'posts:announce-scheduled')))->toBe('*/5 * * * *')
            ->and($commands->first(fn ($expression, $command) => str_contains($command, 'queue:prune-failed')))->not->toBeNull()
            ->and($commands->first(fn ($expression, $command) => str_contains($command, 'cache:prune-expired')))->not->toBeNull();
    });

    test('records a heartbeat', function () {
        $this->artisan('schedule:run');

        expect(Cache::get('scheduler:last_run'))->not->toBeNull();
    });

    test('expired cache rows are pruned', function () {
        config(['cache.default' => 'database']);
        DB::table('cache')->insert([
            ['key' => 'old', 'value' => 'x', 'expiration' => now()->subDay()->getTimestamp()],
            ['key' => 'fresh', 'value' => 'x', 'expiration' => now()->addDay()->getTimestamp()],
        ]);

        $this->artisan('cache:prune-expired')->assertSuccessful();

        expect(DB::table('cache')->pluck('key')->all())->toBe(['fresh']);
    });
});

test('a scheduled article that goes live refreshes the page cache and tells search engines once', function () {
    Queue::fake();
    app(Settings::class)->save(['ai' => ['indexnow_enabled' => true, 'indexnow_key' => str_repeat('k', 32)]]);
    app()->detectEnvironment(fn () => 'production');

    $post = Post::factory()->published()->for(Category::factory()->create())->create(['published_at' => now()->addMinutes(3)]);
    Queue::assertNotPushed(PingIndexNow::class);

    $this->travel(5)->minutes();
    $versionBefore = Cache::get('page-cache:version');
    $this->artisan('posts:announce-scheduled')->assertSuccessful();

    Queue::assertPushed(PingIndexNow::class, fn ($ping) => $ping->url === $post->url());
    expect(Cache::get('page-cache:version'))->not->toBe($versionBefore);

    $this->artisan('posts:announce-scheduled');
    Queue::assertPushed(PingIndexNow::class, 1);
});

test('the live site forces https links', function () {
    app()->detectEnvironment(fn () => 'production');
    (new AppServiceProvider(app()))->boot();

    expect(url('/jobs'))->toStartWith('https://');
});

describe('pages past the end', function () {
    test('category, author and search pages answer not found instead of an empty page', function () {
        $this->withoutVite();
        Category::factory()->create(['slug' => 'career']);

        $this->get('/career?page=99')->assertNotFound();
        $this->get('/search?q=anything&page=99')->assertNotFound();
        $this->get('/career')->assertOk();
    });

    test('the job list falls back to page 1', function () {
        Job::factory()->create(['title' => 'Only job']);

        Livewire::withQueryParams(['page' => 9])->test(JobDirectory::class)->assertSee('Only job');
    });
});

describe('job dates', function () {
    beforeEach(function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    });

    test('an open job cannot have a closing date in the past, or a posted date in the future', function () {
        Livewire::test(CreateJob::class)
            ->fillForm([
                'title' => 'Writer', 'company_name' => 'Acme', 'summary' => 'Write.', 'apply_url' => 'https://acme.example',
                'job_category_id' => JobCategory::factory()->create()->id, 'region_id' => Region::factory()->create()->id,
                'closes_at' => today()->subDay()->toDateString(), 'posted_at' => today()->addDay()->toDateString(),
            ])
            ->call('create')
            ->assertHasFormErrors(['closes_at', 'posted_at']);
    });

    test('a closed job with a past closing date can still be edited', function () {
        $job = Job::factory()->closed()->create(['closes_at' => today()->subMonth()]);

        Livewire::test(EditJob::class, ['record' => $job->getRouteKey()])
            ->fillForm(['title' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($job->fresh()->status)->toBe(JobStatus::Closed);
    });
});

describe('images', function () {
    test('files that are not images are refused politely', function () {
        expect(fn () => app(ImageProcessor::class)->storeScaled(UploadedFile::fake()->create('notes.pdf', 10), 'posts', 1600))
            ->toThrow(ValidationException::class);
    });

    test('replacing the logo in settings deletes the old file', function () {
        Storage::fake('media_local');
        Storage::disk('media_local')->put('branding/old-logo.png', 'x');
        app(Settings::class)->save(['general' => ['logo' => 'branding/old-logo.png']]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSettings::class)->fillForm(['general.logo' => null])->call('save')->assertHasNoFormErrors();

        Storage::disk('media_local')->assertMissing('branding/old-logo.png');
    });
});

test('a visitor\'s message cannot hide links in the owner\'s email', function () {
    $message = ContactMessage::create(['name' => 'Eve', 'email' => 'eve@example.com', 'subject' => 'Hi', 'message' => 'Click [here](https://evil.example) now']);

    expect((new ContactMessageReceived($message))->render())->not->toContain('href="https://evil.example"');
});

test('after signing up, visitors are only ever sent back to this site', function () {
    config(['honeypot.enabled' => false]);
    Notification::fake();

    $this->withHeader('referer', 'https://evil.example/phish')
        ->post('/newsletter', ['email' => 'ada@example.com', 'form_id' => 'newsletter-homepage'])
        ->assertRedirect(url('/').'#newsletter-homepage');
});

test('page cache flushing never fails before the first page is cached', function () {
    Cache::forget('page-cache:version');

    PageCache::flush();

    expect(Cache::get('page-cache:version'))->not->toBeNull();
});
