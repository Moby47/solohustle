<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\ArticleBody;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    config(['site.page_cache' => true]);
    Page::create(['title' => 'About us', 'slug' => 'about', 'body' => '<p>Version one</p>', 'is_published' => true]);
});

test('visitors get cached pages; the second visit is a hit', function () {
    $this->get('/about')->assertHeader('X-Page-Cache', 'miss');
    $this->get('/about')->assertHeader('X-Page-Cache', 'hit')->assertSee('Version one');
});

test('editing content clears the cache straight away', function () {
    $this->get('/about');

    Page::where('slug', 'about')->first()->update(['body' => '<p>Version two</p>']);

    $this->get('/about')->assertHeader('X-Page-Cache', 'miss')->assertSee('Version two');
});

test('saving settings clears the cache', function () {
    $this->get('/about');

    app(Settings::class)->save(['general' => ['site_name' => 'Hustle HQ']]);

    $this->get('/about')->assertSee('Hustle HQ');
});

test('signed-in people never get or fill the cache', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/about')
        ->assertHeaderMissing('X-Page-Cache')
        ->assertSee('Edit in dashboard');
});

test('each visitor gets their own form security token, not the cached one', function () {
    $first = $this->get('/')->getContent();
    preg_match('/name="_token" value="([^"]+)"/', $first, $firstToken);

    $this->flushSession();
    $second = $this->get('/')->assertHeader('X-Page-Cache', 'hit')->getContent();
    preg_match('/name="_token" value="([^"]+)"/', $second, $secondToken);

    expect($secondToken[1] ?? null)->toBe(csrf_token())
        ->and($secondToken[1] ?? null)->not->toBe($firstToken[1] ?? null);
});

test('thank-you messages and form errors are never hidden by the cache', function () {
    $this->get('/about');

    $this->withSession(['_flash' => ['old' => ['contact_status'], 'new' => []], 'contact_status' => 'Thanks!'])
        ->get('/about')
        ->assertHeaderMissing('X-Page-Cache');
});

test('search results and errors are not cached', function () {
    $this->get('/search?q=x')->assertHeaderMissing('X-Page-Cache');
    $this->get('/nope')->assertNotFound()->assertHeaderMissing('X-Page-Cache');
});

test('images inside articles load lazily', function () {
    $body = ArticleBody::from('<p>Hi</p><img src="/a.webp" alt="A">');

    expect((string) $body->beforeBreak)->toContain('loading="lazy"')->toContain('decoding="async"');
});

test('a writer\'s profile change refreshes their bylines', function () {
    $author = User::factory()->create(['name' => 'Ada Writer']);
    Post::factory()->published()->for(Category::factory()->create(['slug' => 'career']))->create(['author_id' => $author->id, 'slug' => 'guide', 'published_at' => now()->subDay()]);
    $this->get('/career/guide')->assertSee('Ada Writer');

    $author->update(['name' => 'Ada Lovelace']);

    $this->get('/career/guide')->assertSee('Ada Lovelace');
});
