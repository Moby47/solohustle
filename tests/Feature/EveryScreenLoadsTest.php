<?php

declare(strict_types=1);

use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media_local');
    $this->seed(DemoSeeder::class);
});

test('every owner dashboard screen opens with real data', function () {
    $this->actingAs(User::where('email', 'admin@solohustle.test')->first());

    $post = Post::first();
    $job = Job::first();
    $writer = User::where('role', 'writer')->first();

    $urls = [
        '/admin', '/admin/posts', '/admin/posts/create', "/admin/posts/{$post->id}/edit",
        '/admin/categories', '/admin/tags', '/admin/pages', '/admin/pages/create', '/admin/pages/'.Page::first()->id.'/edit',
        '/admin/jobs', '/admin/jobs/create', "/admin/jobs/{$job->id}/edit", '/admin/job-categories', '/admin/regions',
        '/admin/writers', "/admin/writers/{$writer->id}", '/admin/inbox', '/admin/inbox/'.ContactMessage::first()->id,
        '/admin/subscribers', '/admin/redirects', '/admin/redirects/create', '/admin/settings',
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }
});

test('every writer screen opens with real data', function () {
    $writer = User::where('email', 'writer@solohustle.test')->first();
    $this->actingAs($writer);

    $this->get('/writer')->assertOk();
    $this->get('/writer/posts')->assertOk();
    $this->get('/writer/posts/create')->assertOk();
    $this->get('/writer/profile')->assertOk();

    $writer->posts()->get()->each(fn (Post $post) => $this->get($post->writerUrl())->assertOk());
});
