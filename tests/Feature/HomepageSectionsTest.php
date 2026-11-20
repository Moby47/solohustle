<?php

declare(strict_types=1);

use App\Models\Job;
use App\Models\Post;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function saveHomepage(array $sections): void
{
    app(Settings::class)->save(['homepage' => $sections]);
}

test('shows every section in order with content from settings', function () {
    saveHomepage([
        'hero' => ['headline' => 'Find work you love'],
        'jobs' => ['title' => 'Fresh jobs'],
        'guides' => ['title' => 'Must-read guides'],
        'creator' => ['title' => 'Write with us'],
        'articles' => ['title' => 'New on the blog'],
        'resource' => ['visible' => true, 'title' => 'Free CV template', 'text' => 'Download it.', 'link_label' => 'Get it', 'link' => '/about'],
    ]);
    Job::factory()->create(['title' => 'Remote writer']);
    Post::factory()->published()->create(['title' => 'Featured guide', 'is_featured' => true, 'published_at' => now()->subDay()]);
    Post::factory()->published()->create(['title' => 'Latest article', 'published_at' => now()->subDay()]);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Find work you love', 'Fresh jobs', 'Remote writer', 'Must-read guides', 'Featured guide', 'Write with us', 'New on the blog', 'Latest article', 'Free CV template', 'Get it']);
});

test('sections can be hidden', function () {
    Job::factory()->create(['title' => 'Remote writer']);
    saveHomepage(['jobs' => ['visible' => false], 'creator' => ['visible' => false]]);

    $this->get('/')
        ->assertDontSee('Remote writer')
        ->assertDontSee(setting('homepage.creator.title'));
});

test('sections with nothing to show stay out of the way', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee(setting('homepage.jobs.title'))
        ->assertDontSee(setting('homepage.guides.title'))
        ->assertDontSee(setting('homepage.articles.title'));
});

test('the number of jobs and articles shown follows the settings; featured articles are not repeated', function () {
    saveHomepage(['jobs' => ['count' => 2], 'articles' => ['count' => 1]]);
    Job::factory()->count(4)->sequence(fn ($s) => ['title' => "Job number {$s->index}"])->create();
    Post::factory()->published()->create(['title' => 'Star guide', 'is_featured' => true, 'published_at' => now()->subHour()]);
    Post::factory()->published()->create(['title' => 'Plain article', 'published_at' => now()->subDays(2)]);

    $response = $this->get('/');

    expect(substr_count($response->getContent(), 'aria-label="Job details"'))->toBe(2)
        ->and(substr_count($response->getContent(), 'Star guide'))->toBe(1);
    $response->assertSee('Plain article');
});

test('the job search box sends visitors to the job list', function () {
    Job::factory()->create();

    $this->get('/')->assertSee('action="'.url('/jobs').'"', false)->assertSee('name="q"', false);
});
