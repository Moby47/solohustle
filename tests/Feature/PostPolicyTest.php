<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('writers can edit and delete their own posts only while they are drafts or sent back', function (PostStatus $status, bool $allowed) {
    $writer = User::factory()->create();
    $post = Post::factory()->for($writer, 'author')->status($status)->create();

    expect($writer->can('update', $post))->toBe($allowed)
        ->and($writer->can('delete', $post))->toBe($allowed)
        ->and($writer->can('submit', $post))->toBe($allowed);
})->with([
    'draft' => [PostStatus::Draft, true],
    'changes requested' => [PostStatus::ChangesRequested, true],
    'pending' => [PostStatus::Pending, false],
    'published' => [PostStatus::Published, false],
    'rejected' => [PostStatus::Rejected, false],
]);

test('writers cannot see or touch other writers\' posts', function () {
    $writer = User::factory()->create();
    $someoneElsesDraft = Post::factory()->create();

    expect($writer->can('view', $someoneElsesDraft))->toBeFalse()
        ->and($writer->can('update', $someoneElsesDraft))->toBeFalse();
});

test('writers can never publish', function () {
    expect(User::factory()->create()->can('publish', Post::class))->toBeFalse();
});

test('suspended writers can do nothing', function () {
    $writer = User::factory()->create();
    $post = Post::factory()->for($writer, 'author')->create();
    $writer->forceFill(['suspended_at' => now()])->save();

    expect($writer->can('create', Post::class))->toBeFalse()
        ->and($writer->can('update', $post))->toBeFalse();
});

test('the owner can do everything', function () {
    $admin = User::factory()->admin()->create();
    $post = Post::factory()->published()->create();

    expect($admin->can('update', $post))->toBeTrue()
        ->and($admin->can('publish', Post::class))->toBeTrue()
        ->and($admin->can('delete', $post))->toBeTrue();
});
