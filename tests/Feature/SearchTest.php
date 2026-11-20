<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Job;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTruncation;

// MySQL full-text indexes can't see rows inside an uncommitted transaction, so commit for real and truncate after.
uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->withoutVite();
    $this->category = Category::factory()->create(['name' => 'Career', 'slug' => 'career']);
});

test('finds published articles by words in the title (even partial) or the text', function () {
    Post::factory()->published()->for($this->category)->create(['title' => 'Freelancing pricing guide', 'published_at' => now()->subDay()]);
    Post::factory()->published()->for($this->category)->create([
        'title' => 'Working from home',
        'body' => '<p>Negotiation tactics that actually work for remote contractors.</p>',
        'published_at' => now()->subDay(),
    ]);
    Post::factory()->for($this->category)->create(['title' => 'Freelancing secrets draft', 'status' => PostStatus::Draft]);

    $this->get('/search?q=freelanc')->assertOk()->assertSee('Freelancing pricing guide')->assertDontSee('Freelancing secrets draft');
    $this->get('/search?q=negotiation')->assertSee('Working from home');
});

test('finds open jobs by title, company or description, and never closed ones', function () {
    Job::factory()->create(['title' => 'Copywriter', 'company_name' => 'Brightpath']);
    Job::factory()->create(['title' => 'Support hero', 'summary' => 'Help customers through Zendesk tickets every day.']);
    Job::factory()->closed()->create(['title' => 'Copywriter (old)', 'company_name' => 'Gone Ltd']);

    $this->get('/search?q=bright')->assertSee('Copywriter')->assertSee('See all matching jobs');
    $this->get('/search?q=zendesk')->assertSee('Support hero');
    $this->get('/search?q=copywriter')->assertDontSee('Copywriter (old)');
});

test('an empty search suggests where to go; no results is friendly', function () {
    $this->get('/search')->assertOk()->assertSee('Search for a job title')->assertSee('Career');
    $this->get('/search?q=zzqqxx')->assertOk()->assertSeeText('Nothing found for "zzqqxx"');
});

test('search pages are hidden from search engines and the header links to search', function () {
    $this->get('/search?q=x')->assertSee('noindex', false);
    $this->get('/career')->assertSee('href="'.route('search').'"', false);
});
