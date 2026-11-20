<?php

declare(strict_types=1);

use App\Enums\JobStatus;
use App\Filament\Resources\JobCategories\Pages\ManageJobCategories;
use App\Filament\Resources\Jobs\JobResource;
use App\Filament\Resources\Jobs\Pages\CreateJob;
use App\Filament\Resources\Jobs\Pages\EditJob;
use App\Filament\Resources\Jobs\Pages\ListJobs;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Redirect;
use App\Models\Region;
use App\Models\User;
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
    $this->actingAs(User::factory()->admin()->create());
});

test('the owner adds a job; it starts open, posted today and checked now', function () {
    $category = JobCategory::factory()->create(['slug' => 'seo']);

    Livewire::test(CreateJob::class)
        ->fillForm([
            'title' => 'SEO Writer',
            'company_name' => 'Acme',
            'job_category_id' => $category->id,
            'region_id' => Region::factory()->create()->id,
            'country_code' => 'NG',
            'summary' => 'Write helpful guides.',
            'requirements' => "Great English\n\n  Two years' experience  ",
            'apply_url' => 'https://acme.example/careers/seo-writer',
            'company_logo' => [UploadedFile::fake()->image('logo.png', 600, 300)],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $job = Job::firstOrFail();

    expect($job->status)->toBe(JobStatus::Open)
        ->and($job->posted_at->isToday())->toBeTrue()
        ->and($job->last_verified_at->isToday())->toBeTrue()
        ->and($job->slug)->toBe('seo-writer-acme')
        ->and($job->path())->toBe('/jobs/seo/seo-writer-acme')
        ->and($job->requirementsList())->toBe(['Great English', 'Two years\' experience'])
        ->and($job->countryName())->toBe('Nigeria')
        ->and($job->company_logo)->toEndWith('.webp');

    [$width, $height] = getimagesizefromstring(Storage::disk('media_local')->get($job->company_logo));
    expect([$width, $height])->toBe([200, 100]);
});

test('the apply link is required and must be a web address', function () {
    Livewire::test(CreateJob::class)
        ->fillForm(['title' => 'X', 'company_name' => 'Y', 'summary' => 'Z', 'apply_url' => 'javascript:alert(1)'])
        ->call('create')
        ->assertHasFormErrors(['apply_url', 'job_category_id', 'region_id']);
});

test('mark verified, close and reopen', function () {
    $job = Job::factory()->create(['last_verified_at' => now()->subDays(40)]);

    Livewire::test(ListJobs::class)->callTableAction('markVerified', $job);
    expect($job->fresh()->last_verified_at->isToday())->toBeTrue();

    Livewire::test(EditJob::class, ['record' => $job->getRouteKey()])->callAction('close');
    expect($job->fresh())->status->toBe(JobStatus::Closed)->closed_at->not->toBeNull();

    Livewire::test(EditJob::class, ['record' => $job->getRouteKey()])->callAction('reopen');
    expect($job->fresh())->status->toBe(JobStatus::Open)->closed_at->toBeNull();
});

test('duplicate makes an open copy with its own logo file and opens it', function () {
    $job = Job::factory()->closed()->create(['company_logo' => 'logos/original.webp', 'posted_at' => now()->subMonth()]);
    Storage::disk('media_local')->put('logos/original.webp', 'logo');

    Livewire::test(EditJob::class, ['record' => $job->getRouteKey()])
        ->callAction('duplicate')
        ->assertRedirect(JobResource::getUrl('edit', ['record' => Job::latest('id')->first()]));

    $copy = Job::latest('id')->first();

    expect($copy->title)->toBe($job->title.' (copy)')
        ->and($copy->status)->toBe(JobStatus::Open)
        ->and($copy->posted_at->isToday())->toBeTrue()
        ->and($copy->slug)->not->toBe($job->slug)
        ->and($copy->company_logo)->not->toBe('logos/original.webp');

    $copy->delete();
    Storage::disk('media_local')->assertExists('logos/original.webp');
});

test('changing a job\'s address or category redirects the old address', function () {
    $job = Job::factory()->create(['slug' => 'writer-acme', 'job_category_id' => JobCategory::factory()->create(['slug' => 'seo'])->id]);

    $job->update(['job_category_id' => JobCategory::factory()->create(['slug' => 'content-writing'])->id]);

    expect(Redirect::where('from_path', '/jobs/seo/writer-acme')->value('to_path'))->toBe('/jobs/content-writing/writer-acme');
});

test('a job category cannot use the address "remote"', function () {
    Livewire::test(ManageJobCategories::class)
        ->callAction('create', ['name' => 'Remote', 'slug' => 'remote'])
        ->assertHasFormErrors(['slug']);
});

test('the essentials seeder adds job categories linked to guides, and regions with Worldwide', function () {
    $this->seed(EssentialsSeeder::class);

    expect(JobCategory::orderBy('sort')->pluck('slug')->all())
        ->toBe(['content-writing', 'seo', 'customer-support', 'design', 'development', 'marketing', 'virtual-assistant'])
        ->and(JobCategory::where('slug', 'seo')->first()->relatedCategory->slug)->toBe('seo-content')
        ->and(Region::where('is_worldwide', true)->pluck('name')->all())->toBe(['Worldwide'])
        ->and(Region::count())->toBe(7)
        ->and(Category::count())->toBe(4);
});
