<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Filament\Pages\ManageSettings;
use App\Jobs\PingIndexNow;
use App\Models\Category;
use App\Models\Job;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function launch(array $ai = []): void
{
    app(Settings::class)->save(['seo' => ['discourage_search_engines' => false], 'ai' => $ai]);
}

describe('robots.txt', function () {
    test('blocks everything before launch', function () {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText("User-agent: *\nDisallow: /");
    });

    test('after launch allows the site, hides private areas and points to the sitemap', function () {
        launch();

        $robots = $this->get('/robots.txt')->getContent();

        expect($robots)->toContain("User-agent: *\nAllow: /")
            ->toContain('Disallow: /admin')
            ->toContain('Disallow: /writer')
            ->toContain('Sitemap: '.url('/sitemap.xml'))
            ->not->toContain('GPTBot');
    });

    test('respects the AI crawler switches', function () {
        launch(['crawlers' => ['gptbot' => false, 'oai_searchbot' => true, 'claudebot' => true, 'perplexitybot' => true, 'google_extended' => false, 'ccbot' => true]]);

        $robots = $this->get('/robots.txt')->getContent();

        expect($robots)->toContain("User-agent: GPTBot\nDisallow: /")
            ->toContain("User-agent: Google-Extended\nDisallow: /")
            ->not->toContain('User-agent: ClaudeBot')
            ->not->toContain('User-agent: OAI-SearchBot');
    });
});

describe('llms.txt', function () {
    test('lists key pages, job pages, topics and recent articles', function () {
        $category = Category::factory()->create(['name' => 'Career', 'slug' => 'career', 'description' => 'Grow your career.']);
        Page::create(['title' => 'About us', 'slug' => 'about', 'is_published' => true]);
        Post::factory()->published()->for($category)->create(['title' => 'Find remote work', 'slug' => 'find-remote-work', 'excerpt' => 'A plan.', 'published_at' => now()->subDay()]);
        Post::factory()->for($category)->create(['title' => 'Unfinished draft', 'status' => PostStatus::Draft]);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSeeText('# Solo Hustle')
            ->assertSeeText('- [About us]('.url('/about').')')
            ->assertSeeText('- [Remote jobs]('.url('/jobs/remote').')')
            ->assertSeeText('- [Career]('.url('/career').'): Grow your career.')
            ->assertSeeText('- [Find remote work]('.url('/career/find-remote-work').'): A plan.')
            ->assertDontSeeText('Unfinished draft');
    });

    test('is refreshed when an article is published', function () {
        $category = Category::factory()->create();
        $this->get('/llms.txt')->assertDontSeeText('Brand new guide');

        Post::factory()->published()->for($category)->create(['title' => 'Brand new guide', 'published_at' => now()->subMinute()]);

        $this->get('/llms.txt')->assertSeeText('Brand new guide');
    });

    test('can be switched off', function () {
        app(Settings::class)->save(['ai' => ['llms_txt' => false]]);

        $this->get('/llms.txt')->assertNotFound();
    });
});

describe('IndexNow', function () {
    beforeEach(function () {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    });

    test('switching it on creates a key, keeps it on later saves, and serves the key file', function () {
        Livewire::test(ManageSettings::class)->fillForm(['ai.indexnow_enabled' => true])->call('save')->assertHasNoFormErrors();
        $key = setting('ai.indexnow_key');

        expect($key)->toMatch('/^[a-z0-9]{32}$/');

        Livewire::test(ManageSettings::class)->fillForm(['general.site_name' => 'Renamed'])->call('save');
        expect(setting('ai.indexnow_key'))->toBe($key);

        $this->get("/{$key}.txt")->assertOk()->assertSeeText($key);
        $this->get('/'.str_repeat('a', 32).'.txt')->assertNotFound();
    });

    test('pings only on the live site, only when on, for published articles and jobs', function () {
        Queue::fake();
        $category = Category::factory()->create(['slug' => 'career']);

        Post::factory()->published()->for($category)->create(['published_at' => now()->subMinute()]);
        Queue::assertNothingPushed();

        app(Settings::class)->save(['ai' => ['indexnow_enabled' => true, 'indexnow_key' => str_repeat('k', 32)]]);
        app()->detectEnvironment(fn () => 'production');

        Post::factory()->for($category)->create(['title' => 'Still a draft']);
        Queue::assertNothingPushed();

        $live = Post::factory()->published()->for($category)->create(['slug' => 'live-one', 'published_at' => now()->subMinute()]);
        $job = Job::factory()->create();
        $job->markVerified();
        $job->close();

        Queue::assertPushed(PingIndexNow::class, fn (PingIndexNow $ping) => $ping->url === $live->url());
        Queue::assertPushed(PingIndexNow::class, fn (PingIndexNow $ping) => $ping->url === $job->url());
        Queue::assertPushed(PingIndexNow::class, 3);
    });

    test('the ping sends the address with the key', function () {
        Http::fake();
        app(Settings::class)->save(['ai' => ['indexnow_key' => str_repeat('k', 32)]]);

        (new PingIndexNow('https://solohustle.test/career/x'))->handle();

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.indexnow.org/indexnow'
            && $request['key'] === str_repeat('k', 32)
            && $request['urlList'] === ['https://solohustle.test/career/x']);
    });
});

test('the AI & search engines tab shows every crawler switch, all on by default', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageSettings::class)
        ->assertFormSet(['ai.crawlers.gptbot' => true, 'ai.crawlers.ccbot' => true, 'ai.llms_txt' => true, 'ai.indexnow_enabled' => false]);
});
