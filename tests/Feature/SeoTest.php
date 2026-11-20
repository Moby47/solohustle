<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Job;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\Settings;
use App\Support\SitemapBuilder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->category = Category::factory()->create(['name' => 'Career', 'slug' => 'career']);
});

function liveArticle(array $attributes = []): Post
{
    return Post::factory()->published()->for(test()->category)->create([
        'title' => 'How to negotiate pay',
        'slug' => 'negotiate-pay',
        'excerpt' => 'A calm, practical script.',
        'published_at' => now()->subWeek(),
        ...$attributes,
    ]);
}

test('every page has a title, description, canonical, Open Graph and Twitter tags', function () {
    liveArticle();

    $this->get('/career/negotiate-pay')
        ->assertSee('<title>How to negotiate pay | Solo Hustle</title>', false)
        ->assertSee('<meta name="description" content="A calm, practical script.">', false)
        ->assertSee('<link rel="canonical" href="'.url('/career/negotiate-pay').'">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:title" content="How to negotiate pay">', false)
        ->assertSee('<meta name="twitter:card"', false);
});

test('the description falls back to the site default', function () {
    $this->get('/')->assertSee('<meta name="description" content="'.setting('seo.default_meta_description').'">', false);
});

test('pages are hidden from search engines until launch, then indexable', function () {
    $this->get('/')->assertSee('noindex, nofollow', false);

    app(Settings::class)->save(['seo' => ['discourage_search_engines' => false]]);

    $this->get('/')->assertDontSee('noindex', false);
});

test('every page describes the organisation and the site search', function () {
    $this->get('/')
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('"sameAs":["https://x.com/solohustle_test"', false)
        ->assertSee('"@type":"SearchAction"', false)
        ->assertSee(route('search').'?q={search_term_string}', false);
});

test('articles describe themselves, their author, breadcrumbs and FAQs; dateModified matches the visible date', function () {
    $author = User::factory()->create(['name' => 'Ada Writer']);
    $post = liveArticle(['author_id' => $author->id, 'faqs' => [['question' => 'Is it rude?', 'answer' => 'No.']]]);
    $this->travel(2)->days();
    $post->update(['body' => '<p>Updated text</p>']);

    $html = $this->get('/career/negotiate-pay')->assertSee('"@type":"Article"', false)->getContent();

    preg_match('/"dateModified":"([^"]+)"/', $html, $modified);
    expect($modified[1])->toStartWith($post->fresh()->content_updated_at->format('Y-m-d'))
        ->and($html)->toContain('Updated <time datetime="'.$post->fresh()->content_updated_at->toIso8601String().'"')
        ->and($html)->toContain('"author":{"@type":"Person","name":"Ada Writer","url":"'.url('/authors/ada-writer').'"}')
        ->and($html)->toContain('"@type":"BreadcrumbList"')
        ->and($html)->toContain('"@type":"FAQPage"');
});

test('Search Console and Bing verification tags appear only when set', function () {
    $this->get('/')->assertDontSee('google-site-verification', false)->assertDontSee('msvalidate.01', false);

    app(Settings::class)->save([
        'integrations' => ['search_console_code' => 'abc123'],
        'ai' => ['bing_verification' => 'BING999'],
    ]);

    $this->get('/')
        ->assertSee('<meta name="google-site-verification" content="abc123">', false)
        ->assertSee('<meta name="msvalidate.01" content="BING999">', false);
});

test('Google Analytics: never locally, and on the live site only with the consent bar', function () {
    app(Settings::class)->save(['integrations' => ['ga4_id' => 'G-TEST123']]);

    $this->get('/')->assertDontSee('G-TEST123')->assertDontSee('data-cookie-consent', false);

    app()->detectEnvironment(fn () => 'production');

    $this->get('/')
        ->assertSee('window.siteAnalyticsId = \'G-TEST123\'', false)
        ->assertSee('data-cookie-consent', false)
        ->assertSee('Cookie settings')
        ->assertDontSee('googletagmanager.com/gtag/js', false);
});

describe('sitemap', function () {
    test('lists public pages and leaves out drafts, closed jobs and suspended authors\' pages', function () {
        liveArticle();
        liveArticle(['title' => 'Secret', 'slug' => 'secret-draft', 'status' => PostStatus::Draft]);
        $open = Job::factory()->create();
        $closed = Job::factory()->closed()->create();
        Page::create(['title' => 'About', 'slug' => 'about', 'is_published' => true]);
        Page::create(['title' => 'Hidden', 'slug' => 'hidden', 'is_published' => false]);

        $xml = app(SitemapBuilder::class)->build();

        expect($xml)
            ->toContain('<loc>'.url('/').'</loc>')
            ->toContain('<loc>'.url('/career/negotiate-pay').'</loc>')
            ->toContain('<loc>'.url('/career').'</loc>')
            ->toContain('<loc>'.$open->url().'</loc>')
            ->toContain('<loc>'.url('/about').'</loc>')
            ->not->toContain('secret-draft')
            ->not->toContain($closed->url())
            ->not->toContain(url('/hidden'));
    });

    test('is served at /sitemap.xml and rebuilt every night', function () {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'sitemap:generate'));

        expect($event?->expression)->toBe('30 0 * * *');
    });
});
