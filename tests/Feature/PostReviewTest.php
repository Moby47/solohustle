<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\User;
use App\Notifications\PostChangesRequested;
use App\Notifications\PostPublished;
use App\Notifications\PostRejected;
use App\Notifications\PostSubmittedForReview;
use App\Support\PostReview;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = User::factory()->admin()->create();
    $this->writer = User::factory()->create();
    $this->post = Post::factory()->for($this->writer, 'author')->status(PostStatus::Pending)
        ->create(['submitted_at' => now()]);
    $this->actingAs($this->admin);
});

test('submitting records both confirmations and emails every admin', function () {
    $draft = Post::factory()->for($this->writer, 'author')->create();

    app(PostReview::class)->submit($draft);

    expect($draft->fresh())
        ->status->toBe(PostStatus::Pending)
        ->owns_work_confirmed_at->not->toBeNull()
        ->originality_confirmed_at->not->toBeNull();

    Notification::assertSentTo($this->admin, PostSubmittedForReview::class);
});

test('publish as is', function () {
    Livewire::test(EditPost::class, ['record' => $this->post->getRouteKey()])
        ->callAction('publish')
        ->assertNotified();

    expect($this->post->fresh())
        ->status->toBe(PostStatus::Published)
        ->published_at->not->toBeNull()
        ->reviewed_by->toBe($this->admin->id);

    Notification::assertSentTo($this->writer, PostPublished::class, fn (PostPublished $n) => $n->withEdits === false);
});

test('save my edits and publish keeps the edits and tells the writer', function () {
    Livewire::test(EditPost::class, ['record' => $this->post->getRouteKey()])
        ->fillForm(['title' => 'A sharper title'])
        ->callAction('publishWithEdits');

    expect($this->post->fresh())
        ->title->toBe('A sharper title')
        ->status->toBe(PostStatus::Published);

    Notification::assertSentTo($this->writer, PostPublished::class, fn (PostPublished $n) => $n->withEdits === true);
});

test('ask for changes needs a note and lets the writer edit again', function () {
    Livewire::test(EditPost::class, ['record' => $this->post->getRouteKey()])
        ->callAction('requestChanges', ['note' => ''])
        ->assertHasActionErrors(['note' => 'required']);

    Livewire::test(EditPost::class, ['record' => $this->post->getRouteKey()])
        ->callAction('requestChanges', ['note' => 'Please add an example.']);

    $post = $this->post->fresh();

    expect($post->status)->toBe(PostStatus::ChangesRequested)
        ->and($post->review_note)->toBe('Please add an example.')
        ->and($this->writer->can('update', $post))->toBeTrue();

    Notification::assertSentTo($this->writer, PostChangesRequested::class);
});

test('reject needs a note and is final', function () {
    Livewire::test(EditPost::class, ['record' => $this->post->getRouteKey()])
        ->callAction('reject', ['note' => 'Too close to an existing guide.']);

    $post = $this->post->fresh();

    expect($post->status)->toBe(PostStatus::Rejected)
        ->and($this->writer->can('update', $post))->toBeFalse();

    Notification::assertSentTo($this->writer, PostRejected::class);
});

test('review buttons only show for articles awaiting review', function () {
    $published = Post::factory()->published()->create();

    Livewire::test(EditPost::class, ['record' => $published->getRouteKey()])
        ->assertActionHidden('publish')
        ->assertActionHidden('reject');
});

test('a failing mailer never blocks a review', function () {
    Notification::shouldReceive('send')->andThrow(new RuntimeException('Mail server down'));

    app(PostReview::class)->accept($this->post, $this->admin);

    expect($this->post->fresh()->status)->toBe(PostStatus::Published);
});

test('the owner reviewing their own article gets no email', function () {
    $own = Post::factory()->for($this->admin, 'author')->status(PostStatus::Pending)->create();

    app(PostReview::class)->accept($own, $this->admin);

    Notification::assertNothingSentTo($this->admin);
});

test('the emails render', function () {
    $this->post->forceFill(['review_note' => "Line one\nLine two"])->save();

    foreach ([new PostSubmittedForReview($this->post), new PostPublished($this->post, true), new PostChangesRequested($this->post), new PostRejected($this->post)] as $notification) {
        expect((string) $notification->toMail($this->writer)->render())->toContain($this->post->title);
    }
});

describe('address changes on live articles', function () {
    test('changing the web address redirects the old one', function () {
        $post = Post::factory()->published()->for(Category::factory()->create(['slug' => 'career']))->create(['slug' => 'old-name']);

        $post->update(['slug' => 'new-name']);

        expect(Redirect::where('from_path', '/career/old-name')->value('to_path'))->toBe('/career/new-name');
    });

    test('moving to another category redirects the old address', function () {
        $post = Post::factory()->published()->for(Category::factory()->create(['slug' => 'career']))->create(['slug' => 'guide']);

        $post->update(['category_id' => Category::factory()->create(['slug' => 'freelancing'])->id]);

        expect(Redirect::where('from_path', '/career/guide')->value('to_path'))->toBe('/freelancing/guide');
    });

    test('renaming a category redirects the category page and its live articles', function () {
        $category = Category::factory()->create(['slug' => 'seo']);
        Post::factory()->published()->for($category)->create(['slug' => 'live-one']);
        Post::factory()->for($category)->create(['slug' => 'draft-one']);

        $category->update(['slug' => 'seo-content']);

        expect(Redirect::pluck('to_path', 'from_path')->all())->toEqual([
            '/seo' => '/seo-content',
            '/seo/live-one' => '/seo-content/live-one',
        ]);
    });

    test('drafts never create redirects', function () {
        Post::factory()->create(['slug' => 'draft'])->update(['slug' => 'still-draft']);

        expect(Redirect::count())->toBe(0);
    });
});
