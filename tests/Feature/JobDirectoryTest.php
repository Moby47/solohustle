<?php

declare(strict_types=1);

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\WorkMode;
use App\Livewire\JobDirectory;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seo = JobCategory::factory()->create(['name' => 'SEO', 'slug' => 'seo', 'intro' => 'Search jobs.']);
    $this->design = JobCategory::factory()->create(['name' => 'Design', 'slug' => 'design']);
    $this->worldwide = Region::factory()->worldwide()->create();
    $this->africa = Region::factory()->create(['name' => 'Africa', 'slug' => 'africa']);
    $this->europe = Region::factory()->create(['name' => 'Europe', 'slug' => 'europe']);
});

function job(string $title, array $attributes = []): Job
{
    return Job::factory()->create([
        'title' => $title,
        'job_category_id' => test()->seo->id,
        'region_id' => test()->africa->id,
        ...$attributes,
    ]);
}

test('lists open jobs and hides closed ones unless asked', function () {
    job('Open role');
    job('Closed role', ['status' => 'closed']);

    $this->get('/jobs')->assertOk()->assertSee('Open role')->assertDontSee('Closed role')->assertSeeText('1 job');
    $this->get('/jobs?closed=1')->assertSee('Closed role');
});

test('every filter works from the address bar (no JavaScript needed)', function (string $query, string $shown, string $hidden) {
    job('Remote SEO writer');
    job('Hybrid designer', [
        'job_category_id' => $this->design->id,
        'work_mode' => WorkMode::Hybrid,
        'employment_type' => EmploymentType::PartTime,
        'experience_level' => ExperienceLevel::Senior,
        'region_id' => $this->europe->id,
        'posted_at' => today()->subDays(20),
        'company_name' => 'Pixel Studio',
    ]);

    $this->get('/jobs?'.$query)->assertOk()->assertSee($shown)->assertDontSee($hidden);
})->with([
    'category' => ['category=design', 'Hybrid designer', 'Remote SEO writer'],
    'work mode' => ['mode=hybrid', 'Hybrid designer', 'Remote SEO writer'],
    'type' => ['type=part_time', 'Hybrid designer', 'Remote SEO writer'],
    'experience' => ['level=senior', 'Hybrid designer', 'Remote SEO writer'],
    'region' => ['region=europe', 'Hybrid designer', 'Remote SEO writer'],
    'date posted' => ['posted=7', 'Remote SEO writer', 'Hybrid designer'],
    'keyword in company' => ['q=pixel', 'Hybrid designer', 'Remote SEO writer'],
]);

test('filtering by a region also shows jobs open to applicants anywhere', function () {
    job('Africa role');
    job('Anywhere role', ['region_id' => $this->worldwide->id]);
    job('Europe role', ['region_id' => $this->europe->id]);

    $this->get('/jobs?region=africa')->assertSee('Africa role')->assertSee('Anywhere role')->assertDontSee('Europe role');
});

test('featured jobs come first, then newest', function () {
    job('Older role', ['posted_at' => today()->subDays(5)]);
    job('Newer role', ['posted_at' => today()]);
    job('Featured role', ['posted_at' => today()->subDays(9), 'is_featured' => true]);

    $this->get('/jobs')->assertSeeInOrder(['Featured role', 'Newer role', 'Older role']);
});

test('filters update live and can be cleared', function () {
    job('Remote SEO writer');
    job('Hybrid designer', ['work_mode' => WorkMode::Hybrid]);

    Livewire::test(JobDirectory::class)
        ->set('mode', 'hybrid')
        ->assertSee('Hybrid designer')
        ->assertDontSee('Remote SEO writer')
        ->assertSee('Clear filters')
        ->call('clearFilters')
        ->assertSee('Remote SEO writer')
        ->assertSet('mode', '');
});

test('the remote landing page only shows remote jobs and hides the work mode filter', function () {
    job('Remote SEO writer');
    job('Office designer', ['work_mode' => WorkMode::Onsite]);

    $this->get('/jobs/remote')
        ->assertOk()
        ->assertSee(setting('jobs.remote_headline'))
        ->assertSee('Remote SEO writer')
        ->assertDontSee('Office designer')
        ->assertDontSee('id="job-mode"', false);
});

test('a job category landing page shows its intro and only its jobs; unknown categories are not found', function () {
    job('SEO role');
    job('Design role', ['job_category_id' => $this->design->id]);

    $this->get('/jobs/seo')
        ->assertOk()
        ->assertSee('SEO jobs')
        ->assertSee('Search jobs.')
        ->assertSee('SEO role')
        ->assertDontSee('Design role')
        ->assertDontSee('id="job-category"', false);

    $this->get('/jobs/nope')->assertNotFound();
});

test('the address bar cannot change a landing page\'s fixed category', function () {
    job('Design role', ['job_category_id' => $this->design->id]);

    $this->get('/jobs/seo?category=design')->assertDontSee('Design role');
});

test('pagination links are real links that keep the filters', function () {
    foreach (range(1, JobDirectory::PER_PAGE + 1) as $i) {
        job("Role {$i}", ['work_mode' => WorkMode::Hybrid]);
    }

    $this->get('/jobs?mode=hybrid')
        ->assertSee('href="'.url('/jobs').'?mode=hybrid&amp;page=2"', false);
});

test('nonsense filter values are ignored, not errors', function () {
    job('Any role');

    $this->get('/jobs?mode=spaceship&type=x&level=y&posted=999&region=nowhere')->assertOk();
    $this->get('/jobs?mode=spaceship')->assertSee('Any role');
});

test('a friendly message when nothing matches', function () {
    $this->get('/jobs?q=zzzz')->assertSee('No jobs match these filters');
});
