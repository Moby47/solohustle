<?php

declare(strict_types=1);

use App\Enums\EmploymentType;
use App\Enums\WorkMode;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Post;
use App\Models\Region;
use App\Models\User;
use App\Support\JobPostingSchema;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->guides = Category::factory()->create(['name' => 'SEO & Content', 'slug' => 'seo-content']);
    $this->seo = JobCategory::factory()->create(['name' => 'SEO', 'slug' => 'seo', 'related_category_id' => $this->guides->id]);
    $this->worldwide = Region::factory()->worldwide()->create();
    $this->europe = Region::factory()->create(['name' => 'Europe', 'slug' => 'europe']);
});

function seoJob(array $attributes = []): Job
{
    return Job::factory()->create([
        'title' => 'SEO Writer',
        'company_name' => 'Acme',
        'slug' => 'seo-writer-acme',
        'job_category_id' => test()->seo->id,
        'region_id' => test()->worldwide->id,
        'summary' => "First paragraph.\n\nSecond <b>paragraph</b>.",
        'requirements' => "Great English\nTwo years' experience",
        'apply_url' => 'https://acme.example/apply',
        'salary_text' => '$50k',
        ...$attributes,
    ]);
}

test('an open job shows everything a job seeker needs, with one apply button', function () {
    // The site-wide "discourage search engines" switch is on until launch; turn it off to see per-page robots rules.
    app(Settings::class)->save(['seo' => ['discourage_search_engines' => false]]);
    seoJob(['source_name' => 'Acme careers', 'source_url' => 'https://acme.example/careers']);

    $this->get('/jobs/seo/seo-writer-acme')
        ->assertOk()
        ->assertSeeInOrder(['Jobs', 'SEO', 'SEO Writer'])
        ->assertSee('Acme')
        ->assertSee('href="https://acme.example/apply" rel="nofollow noopener" target="_blank"', false)
        ->assertSee('Second &lt;b&gt;paragraph&lt;/b&gt;.', false)
        ->assertSee('Two years&#039; experience', false)
        ->assertSee('Last checked')
        ->assertSee('Acme careers')
        ->assertSee('"@type":"JobPosting"', false)
        ->assertDontSee('noindex', false);
});

test('a closed job stays viewable with a banner, no apply button, no job data, and noindex', function () {
    seoJob(['status' => 'closed']);

    $this->get('/jobs/seo/seo-writer-acme')
        ->assertOk()
        ->assertSee('This job is closed.')
        ->assertDontSee('https://acme.example/apply')
        ->assertDontSee('JobPosting')
        ->assertSee('noindex', false);
});

test('a job in the wrong category address redirects to the right one', function () {
    seoJob();
    JobCategory::factory()->create(['slug' => 'design']);

    $this->get('/jobs/design/seo-writer-acme')->assertRedirect('/jobs/seo/seo-writer-acme');
});

test('shows similar open jobs and guides from the related blog category', function () {
    seoJob();
    seoJob(['title' => 'SEO Manager', 'slug' => 'seo-manager', 'company_name' => 'Beta']);
    seoJob(['title' => 'Old SEO role', 'slug' => 'old-seo', 'status' => 'closed']);
    Post::factory()->published()->for($this->guides)->create(['title' => 'How to write for search', 'published_at' => now()->subDay()]);

    $this->get('/jobs/seo/seo-writer-acme')
        ->assertSee('More SEO jobs')
        ->assertSee('SEO Manager')
        ->assertDontSee('Old SEO role')
        ->assertSee('Guides to help you land it')
        ->assertSee('How to write for search');
});

test('articles link to the job categories that point at their blog category', function () {
    $post = Post::factory()->published()->for($this->guides)->create(['published_at' => now()->subDay()]);

    $this->get($post->path())->assertSee('Ready to put this into practice?')->assertSee('SEO jobs');
});

test('the owner sees an edit shortcut', function () {
    seoJob();

    $this->actingAs(User::factory()->admin()->create())->get('/jobs/seo/seo-writer-acme')->assertSee('Edit in dashboard');
});

describe('JobPosting data', function () {
    test('a worldwide remote job', function () {
        $schema = JobPostingSchema::for(seoJob(['closes_at' => '2026-12-31']));

        expect($schema)
            ->toMatchArray([
                'title' => 'SEO Writer',
                'datePosted' => today()->toDateString(),
                'employmentType' => 'FULL_TIME',
                'jobLocationType' => 'TELECOMMUTE',
                'directApply' => false,
            ])
            ->and($schema['hiringOrganization']['name'])->toBe('Acme')
            ->and($schema['validThrough'])->toStartWith('2026-12-31T23:59:59')
            ->and($schema['description'])->toContain('<li>Two years&#039; experience</li>')
            ->and($schema)->not->toHaveKey('applicantLocationRequirements');
    });

    test('a remote job limited to a country or region says where applicants can live', function () {
        expect(JobPostingSchema::for(seoJob(['country_code' => 'NG']))['applicantLocationRequirements'])
            ->toBe(['@type' => 'Country', 'name' => 'Nigeria']);

        expect(JobPostingSchema::for(seoJob(['slug' => 'eu-job', 'region_id' => $this->europe->id]))['applicantLocationRequirements'])
            ->toBe(['@type' => 'AdministrativeArea', 'name' => 'Europe']);
    });

    test('an on-site job gives the workplace country', function () {
        $schema = JobPostingSchema::for(seoJob([
            'work_mode' => WorkMode::Onsite,
            'employment_type' => EmploymentType::Freelance,
            'country_code' => 'GB',
            'location_note' => 'London',
        ]));

        expect($schema['jobLocation']['address'])->toMatchArray(['addressLocality' => 'London', 'addressCountry' => 'GB'])
            ->and($schema['employmentType'])->toBe('CONTRACTOR')
            ->and($schema)->not->toHaveKey('jobLocationType');
    });

    test('closed jobs have none', function () {
        expect(JobPostingSchema::for(seoJob(['status' => 'closed'])))->toBeNull();
    });
});
