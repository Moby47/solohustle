<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Filament\Forms\LinkPicker;
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
    $this->category = Category::factory()->create(['name' => 'Career', 'slug' => 'career', 'description' => 'Grow your career.']);
});

function livePost(array $attributes = []): Post
{
    return Post::factory()->published()->for(test()->category)->create([
        'title' => 'How to find remote work',
        'slug' => 'find-remote-work',
        'published_at' => now()->subDay(),
        ...$attributes,
    ]);
}

describe('article page', function () {
    test('shows the full article template', function () {
        livePost([
            'excerpt' => 'A practical plan.',
            'body' => '<h2>Where to look</h2><p>One</p><h2>What to send</h2><p>Two</p><h2>How to follow up</h2><p>Three</p>',
            'quick_answer' => 'Use curated boards and apply early.',
            'key_takeaway' => 'Consistency wins.',
            'faqs' => [['question' => 'Is it hard?', 'answer' => 'Not with a plan.']],
            'sources' => [['title' => 'Remote report', 'url' => 'https://example.com/report']],
        ]);

        $this->get('/career/find-remote-work')
            ->assertOk()
            ->assertSeeInOrder(['Career', 'How to find remote work', 'A practical plan.', 'Quick answer', 'Use curated boards'])
            ->assertSee('In this article')
            ->assertSee('id="where-to-look"', false)
            ->assertSeeInOrder(['What to send', setting('article.mid_cta_text'), 'How to follow up'])
            ->assertSee('Consistency wins.')
            ->assertSee('Is it hard?')
            ->assertSee('href="https://example.com/report" rel="noopener"', false);
    });

    test('drafts, pending and scheduled articles are hidden from visitors', function (array $attributes) {
        livePost($attributes);

        $this->get('/career/find-remote-work')->assertNotFound();
    })->with([
        'draft' => [['status' => PostStatus::Draft]],
        'pending' => [['status' => PostStatus::Pending]],
        'scheduled' => [['published_at' => now()->addWeek()]],
    ]);

    test('the owner and the author can preview an unpublished article; other writers cannot', function () {
        $author = User::factory()->create();
        livePost(['status' => PostStatus::Draft, 'author_id' => $author->id]);

        $this->actingAs($author)->get('/career/find-remote-work')->assertOk()->assertSee('Preview.');
        $this->actingAs(User::factory()->admin()->create())->get('/career/find-remote-work')->assertOk()->assertSee('Edit in dashboard');
        $this->actingAs(User::factory()->create())->get('/career/find-remote-work')->assertNotFound();
    });

    test('the wrong category in the address redirects to the right one', function () {
        livePost();
        Category::factory()->create(['slug' => 'freelancing']);

        $this->get('/freelancing/find-remote-work')->assertRedirect('/career/find-remote-work');
    });

    test('shows an Updated date only after a real content change on a later day', function () {
        $post = livePost(['published_at' => now()->subWeek()]);
        $this->get($post->path())->assertDontSee('Updated');

        $post->update(['body' => '<p>Fresh</p>']);
        $this->get($post->path())->assertSee('Updated');
    });

    test('lists related articles from the same category', function () {
        livePost();
        livePost(['title' => 'Write a great CV', 'slug' => 'great-cv']);
        Post::factory()->published()->create(['title' => 'Other category post', 'published_at' => now()->subDay()]);

        $this->get('/career/find-remote-work')
            ->assertSee('More in Career')
            ->assertSee('Write a great CV')
            ->assertDontSee('Other category post');
    });
});

describe('article body', function () {
    test('headings get unique anchors and the box goes before the third H2', function () {
        $body = ArticleBody::from('<h2>Intro</h2><p>a</p><h3>Intro</h3><h2>Two</h2><p>b</p><h2>Three</h2><p>c</p>');

        expect(array_column($body->headings, 'id'))->toBe(['intro', 'intro-2', 'two', 'three'])
            ->and((string) $body->beforeBreak)->toContain('Two')->not->toContain('Three')
            ->and((string) $body->afterBreak)->toStartWith('<h2 id="three">')
            ->and($body->hasTableOfContents())->toBeTrue();
    });

    test('short articles put the box at the end and skip the table of contents', function () {
        $body = ArticleBody::from('<p>Just a paragraph with ünïcödé.</p>');

        expect((string) $body->afterBreak)->toBe('')
            ->and((string) $body->beforeBreak)->toContain('ünïcödé')
            ->and($body->hasTableOfContents())->toBeFalse();
    });
});

describe('category pages', function () {
    test('list only published articles, newest first', function () {
        livePost(['title' => 'Older', 'slug' => 'older', 'published_at' => now()->subWeek()]);
        livePost(['title' => 'Newer', 'slug' => 'newer', 'published_at' => now()->subDay()]);
        livePost(['title' => 'Secret draft', 'slug' => 'secret', 'status' => PostStatus::Draft]);

        $this->get('/career')
            ->assertOk()
            ->assertSee('Grow your career.')
            ->assertSeeInOrder(['Newer', 'Older'])
            ->assertDontSee('Secret draft');
    });

    test('show a friendly message when empty', function () {
        $this->get('/career')->assertOk()->assertSee('No articles here yet');
    });

    test('pages still work alongside categories, even with no text yet', function () {
        Page::create(['title' => 'About', 'slug' => 'about', 'is_published' => true]);

        $this->get('/about')->assertOk()->assertSee('About');
    });

    test('an article with no text yet can still be previewed', function () {
        livePost(['body' => null]);

        $this->get('/career/find-remote-work')->assertOk();
    });
});

describe('author pages', function () {
    test('show the author, their published articles and Person data', function () {
        $author = User::factory()->create(['name' => 'Ada Writer', 'headline' => 'SEO writer', 'website_url' => 'https://ada.example.com']);
        livePost(['author_id' => $author->id]);

        $this->get('/authors/ada-writer')
            ->assertOk()
            ->assertSee('SEO writer')
            ->assertSee('How to find remote work')
            ->assertSee('"@type":"Person"', false)
            ->assertSee('"sameAs":["https://ada.example.com"]', false);
    });

    test('do not exist for writers without published work, or who are suspended', function () {
        $noWork = User::factory()->create(['name' => 'No Work']);
        $suspended = User::factory()->create(['name' => 'Gone Quiet', 'suspended_at' => now()]);
        livePost(['author_id' => $suspended->id]);

        $this->get('/authors/no-work')->assertNotFound();
        $this->get('/authors/gone-quiet')->assertNotFound();
        $this->get('/career/find-remote-work')->assertOk()->assertDontSee('href="'.url('/authors/gone-quiet').'"', false);
    });
});

describe('publish page', function () {
    test('shows benefits, steps and the sign-up button from settings', function () {
        app(Settings::class)->save(['publish' => ['headline' => 'Write with us', 'cta_label' => 'Join now']]);

        $this->get('/publish')
            ->assertOk()
            ->assertSee('Write with us')
            ->assertSee('How it works')
            ->assertSee('Join now')
            ->assertSee(url('/writer/register'));
    });
});

describe('header and footer', function () {
    test('are built from the Navigation settings', function () {
        app(Settings::class)->save(['navigation' => [
            'header' => [['label' => 'Guides', 'link' => '/career']],
            'cta_label' => 'Write for us',
            'cta_link' => '/publish',
            'footer' => [['title' => 'Legal bits', 'links' => [['label' => 'Terms', 'link' => '/terms']]]],
        ]]);

        $this->get('/career')
            ->assertSee('Guides')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Write for us')
            ->assertSee('Legal bits')
            ->assertSee('X (Twitter)');
    });

    test('app addresses are never treated as pages or articles', function () {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/writer/login')->assertOk();
    });
});

test('the link picker offers main sections, categories and pages, and keeps custom addresses', function () {
    Page::create(['title' => 'About us', 'slug' => 'about', 'is_published' => true]);

    $options = LinkPicker::options('https://partner.example.com');

    expect($options['Main sections'])->toHaveKey('/jobs')
        ->and($options['Blog categories'])->toBe(['/career' => 'Career'])
        ->and($options['Pages'])->toBe(['/about' => 'About us'])
        ->and($options['Other addresses'])->toBe(['https://partner.example.com' => 'https://partner.example.com']);
});
