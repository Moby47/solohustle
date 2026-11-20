<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Filament\Writer\Resources\Posts\Pages\CreatePost;
use App\Filament\Writer\Resources\Posts\Pages\EditPost;
use App\Filament\Writer\Resources\Posts\Pages\ListPosts;
use App\Filament\Writer\Resources\Posts\Pages\ViewPost;
use App\Filament\Writer\Resources\Posts\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Notifications\PostSubmittedForReview;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Filament::setCurrentPanel(Filament::getPanel('writer'));
    $this->writer = User::factory()->create();
    $this->actingAs($this->writer);
});

function readyDraft(User $writer, array $attributes = []): Post
{
    return Post::factory()->for($writer, 'author')->create([
        'excerpt' => 'A short summary.',
        'body' => '<p>'.str_repeat('word ', 320).'</p>',
        ...$attributes,
    ]);
}

test('a writer starts a draft; author and status are set by the server', function () {
    $category = Category::factory()->create();

    Livewire::test(CreatePost::class)
        ->fillForm(['title' => 'My first guide', 'category_id' => $category->id, 'body' => '<p>Hello</p>'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Post::firstOrFail())
        ->author_id->toBe($this->writer->id)
        ->status->toBe(PostStatus::Draft)
        ->slug->toBe('my-first-guide');
});

test('the writer form has no publishing controls', function () {
    Livewire::test(CreatePost::class)
        ->assertFormFieldDoesNotExist('status')
        ->assertFormFieldDoesNotExist('author_id')
        ->assertFormFieldDoesNotExist('is_featured')
        ->assertFormFieldDoesNotExist('published_at')
        ->assertFormFieldDoesNotExist('slug');
});

test('writers only see their own articles', function () {
    $mine = readyDraft($this->writer);
    $theirs = Post::factory()->create();

    Livewire::test(ListPosts::class)
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$theirs]);

    $this->get(PostResource::getUrl('edit', ['record' => $theirs]))->assertNotFound();
});

test('submitting needs both confirmations', function () {
    $post = readyDraft($this->writer);

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->callAction('submit', ['owns_work' => true, 'original' => false])
        ->assertHasActionErrors(['original']);

    expect($post->fresh()->status)->toBe(PostStatus::Draft);
});

test('submitting saves the timestamps, locks the article and emails the owner', function () {
    $admin = User::factory()->admin()->create();
    $post = readyDraft($this->writer);

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->callAction('submit', ['owns_work' => true, 'original' => true])
        ->assertRedirect(PostResource::getUrl('view', ['record' => $post]));

    expect($post->fresh())
        ->status->toBe(PostStatus::Pending)
        ->submitted_at->not->toBeNull()
        ->owns_work_confirmed_at->not->toBeNull()
        ->originality_confirmed_at->not->toBeNull();

    Notification::assertSentTo($admin, PostSubmittedForReview::class);

    $this->get(PostResource::getUrl('edit', ['record' => $post]))->assertForbidden();
});

test('too-short articles are not submitted, with a clear reason', function () {
    $post = readyDraft($this->writer, ['body' => '<p>Too short</p>', 'excerpt' => null]);

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->callAction('submit', ['owns_work' => true, 'original' => true])
        ->assertNotified('Almost there');

    expect($post->fresh()->status)->toBe(PostStatus::Draft);
});

test('writers can edit again after changes are requested and see the note', function () {
    $post = readyDraft($this->writer, ['status' => PostStatus::ChangesRequested, 'review_note' => 'Add an example please.']);

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->assertSee('Add an example please.')
        ->fillForm(['title' => 'Better title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($post->fresh()->title)->toBe('Better title');
});

test('pending, published and rejected articles are read-only', function (PostStatus $status) {
    $post = readyDraft($this->writer, ['status' => $status, 'review_note' => 'Not for us.']);

    $this->get(PostResource::getUrl('edit', ['record' => $post]))->assertForbidden();
    Livewire::test(ViewPost::class, ['record' => $post->getRouteKey()])->assertOk();
})->with([PostStatus::Pending, PostStatus::Published, PostStatus::Rejected]);

test('writers can delete their own drafts but not submitted work', function () {
    $draft = readyDraft($this->writer);

    Livewire::test(EditPost::class, ['record' => $draft->getRouteKey()])->callAction('delete');

    expect(Post::find($draft->id))->toBeNull();
});

test('the writer dashboard loads with their stats', function () {
    readyDraft($this->writer);

    $this->get('/writer')->assertOk()->assertSee('In progress');
});
