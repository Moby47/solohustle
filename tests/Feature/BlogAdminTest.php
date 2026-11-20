<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\ImageProcessor;
use Database\Seeders\EssentialsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('media_local');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

describe('images', function () {
    test('covers become 16:9 WebP files in three widths', function () {
        $path = app(ImageProcessor::class)->storeCropped(
            UploadedFile::fake()->image('cover.jpg', 2000, 2000), 'covers', 1600, 900, [960, 480],
        );

        $disk = Storage::disk('media_local');

        expect($path)->toEndWith('.webp');

        foreach ([[$path, 1600, 900], [str_replace('.webp', '-960.webp', $path), 960, 540], [str_replace('.webp', '-480.webp', $path), 480, 270]] as [$file, $width, $height]) {
            [$actualWidth, $actualHeight, $type] = getimagesizefromstring($disk->get($file));
            expect([$actualWidth, $actualHeight, $type])->toBe([$width, $height, IMAGETYPE_WEBP]);
        }
    });

    test('replacing a cover deletes the old files', function () {
        $processor = app(ImageProcessor::class);
        $old = $processor->storeCropped(UploadedFile::fake()->image('a.jpg', 1600, 900), 'covers', 1600, 900, [480]);
        $post = Post::factory()->create(['cover_image' => $old, 'cover_alt' => 'A desk']);

        $post->update(['cover_image' => $processor->storeCropped(UploadedFile::fake()->image('b.jpg', 1600, 900), 'covers', 1600, 900, [480])]);

        Storage::disk('media_local')->assertMissing($old);
        Storage::disk('media_local')->assertMissing(str_replace('.webp', '-480.webp', $old));
        Storage::disk('media_local')->assertExists($post->cover_image);
    });

    test('images inside articles are converted to WebP and never wider than 1600px', function () {
        $path = app(ImageProcessor::class)->storeScaled(UploadedFile::fake()->image('wide.png', 3000, 1000), 'posts', 1600);

        [$width, $height, $type] = getimagesizefromstring(Storage::disk('media_local')->get($path));
        expect([$width, $height, $type])->toBe([1600, 533, IMAGETYPE_WEBP]);
    });
});

describe('article editor', function () {
    test('the owner can write and publish an article with tags, FAQs and sources', function () {
        $category = Category::factory()->create(['slug' => 'career']);
        $tag = Tag::create(['name' => 'Remote work']);

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'How to find your first remote job',
                'slug' => 'first-remote-job',
                'excerpt' => 'A practical plan.',
                'body' => '<h2>Where should you look?</h2><p>'.str_repeat('word ', 450).'</p>',
                'category_id' => $category->id,
                'tags' => [$tag->id],
                'status' => PostStatus::Published->value,
                'quick_answer' => 'Start with curated boards.',
                'faqs' => [['question' => 'Is it hard?', 'answer' => 'Not with a plan.']],
                'sources' => [['title' => 'Remote report', 'url' => 'https://example.com/report']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::firstOrFail();

        expect($post->author_id)->toBe($this->admin->id)
            ->and($post->status)->toBe(PostStatus::Published)
            ->and($post->published_at)->not->toBeNull()
            ->and($post->reading_minutes)->toBe(3)
            ->and($post->tags->pluck('name')->all())->toBe(['Remote work'])
            ->and($post->faqs[0]['question'])->toBe('Is it hard?')
            ->and($post->url())->toBe(url('/career/first-remote-job'));
    });

    test('a cover uploaded in the editor is saved as WebP with its smaller copies', function () {
        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'With cover',
                'slug' => 'with-cover',
                'category_id' => Category::factory()->create()->id,
                // The browser crops to 16:9 before uploading.
                'cover_image' => [UploadedFile::fake()->image('cover.jpg', 1920, 1080)],
                'cover_alt' => 'A laptop on a desk',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $cover = Post::firstOrFail()->cover_image;

        expect($cover)->toStartWith('covers/')->toEndWith('.webp');
        Storage::disk('media_local')->assertExists([$cover, str_replace('.webp', '-960.webp', $cover), str_replace('.webp', '-480.webp', $cover)]);
    });

    test('a cover needs a description and sources must be web links', function () {
        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Test',
                'slug' => 'test',
                'category_id' => Category::factory()->create()->id,
                'cover_image' => [UploadedFile::fake()->image('cover.jpg', 1600, 900)],
                'sources' => [['title' => 'Bad', 'url' => 'javascript:alert(1)']],
            ])
            ->call('create')
            ->assertHasFormErrors(['cover_alt', 'sources.0.url']);
    });

    test('editing a live article updates its "Updated" date; editing a draft does not', function () {
        $live = Post::factory()->published()->create(['published_at' => now()->subWeek()]);
        $draft = Post::factory()->create();

        $this->travel(1)->hours();
        $live->update(['body' => '<p>New text</p>']);
        $draft->update(['body' => '<p>New text</p>']);

        expect($live->fresh()->content_updated_at)->not->toBeNull()
            ->and($draft->fresh()->content_updated_at)->toBeNull();
    });

    test('the list shows posts awaiting review in their own tab', function () {
        $pending = Post::factory()->status(PostStatus::Pending)->create();
        $draft = Post::factory()->create();

        Livewire::test(ListPosts::class)
            ->set('activeTab', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$draft]);
    });

    test('the edit form loads', function () {
        Livewire::test(EditPost::class, ['record' => Post::factory()->create()->getRouteKey()])->assertOk();
    });
});

describe('categories', function () {
    test('a category cannot take an address used by a page, and vice versa', function () {
        Page::create(['title' => 'About', 'slug' => 'about', 'is_published' => true]);

        Livewire::test(ManageCategories::class)
            ->callAction('create', ['name' => 'About', 'slug' => 'about'])
            ->assertHasFormErrors(['slug']);
    });

    test('a category with articles cannot be deleted', function () {
        $category = Category::factory()->create();
        Post::factory()->for($category)->create();

        Livewire::test(ManageCategories::class)
            ->assertTableActionDisabled('delete', $category);
    });
});

test('the essentials seeder adds the four blog categories in order', function () {
    $this->seed(EssentialsSeeder::class);

    expect(Category::orderBy('sort')->pluck('slug')->all())
        ->toBe(['career', 'freelancing', 'seo-content', 'reviews-guides']);
});
