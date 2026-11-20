<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Filament\Resources\Writers\Pages\ListWriters;
use App\Filament\Resources\Writers\Pages\ViewWriter;
use App\Filament\Resources\Writers\RelationManagers\PostsRelationManager;
use App\Filament\Resources\Writers\WriterResource;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('lists writers only, not admins', function () {
    $writer = User::factory()->create();

    Livewire::test(ListWriters::class)
        ->assertCanSeeTableRecords([$writer])
        ->assertCanNotSeeTableRecords([$this->admin]);
});

test('shows how many articles each writer has published', function () {
    $writer = User::factory()->create();
    Post::factory()->for($writer, 'author')->published()->count(2)->create();
    Post::factory()->for($writer, 'author')->create();

    Livewire::test(ListWriters::class)
        ->assertTableColumnStateSet('published_posts_count', 2, $writer)
        ->assertTableColumnStateSet('posts_count', 3, $writer);
});

test('suspend and unsuspend from the list', function () {
    $writer = User::factory()->create();

    Livewire::test(ListWriters::class)
        ->callTableAction('suspend', $writer)
        ->assertNotified();

    expect($writer->fresh()->isSuspended())->toBeTrue();

    Livewire::test(ListWriters::class)
        ->assertTableActionHidden('suspend', $writer)
        ->callTableAction('unsuspend', $writer);

    expect($writer->fresh()->isSuspended())->toBeFalse();
});

test('a suspended writer is locked out and loses their author page', function () {
    $writer = User::factory()->create();
    Post::factory()->for($writer, 'author')->published()->create();
    expect($writer->hasAuthorPage())->toBeTrue();

    Livewire::test(ViewWriter::class, ['record' => $writer->getRouteKey()])->callAction('suspend');

    $writer->refresh();
    expect($writer->hasAuthorPage())->toBeFalse()
        ->and($writer->canAccessPanel(Filament::getPanel('writer')))->toBeFalse();
});

test('the writer page lists their articles', function () {
    $writer = User::factory()->create();
    $post = Post::factory()->for($writer, 'author')->status(PostStatus::Pending)->create();

    Livewire::test(PostsRelationManager::class, ['ownerRecord' => $writer, 'pageClass' => ViewWriter::class])
        ->assertCanSeeTableRecords([$post]);
});

test('admins cannot be opened as writers', function () {
    $this->get(WriterResource::getUrl('view', ['record' => $this->admin]))->assertNotFound();
});
