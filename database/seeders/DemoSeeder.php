<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Enums\WorkMode;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Post;
use App\Models\Region;
use App\Models\Subscriber;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\Demo\PlaceholderImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Realistic-looking demo content for local testing only. Never runs on the live site.
 * Reset everything with: php artisan migrate:fresh --seed
 */
class DemoSeeder extends Seeder
{
    private const DEMO_DOMAIN = 'demo.solohustle.test';

    public function __construct(private readonly PlaceholderImages $images) {}

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder never runs in production.');

            return;
        }

        if (User::where('email', 'like', '%@'.self::DEMO_DOMAIN)->exists()) {
            $this->command?->warn('Demo content is already there. To start over: php artisan migrate:fresh --seed');

            return;
        }

        $this->call(EssentialsSeeder::class);

        $admin = $this->demoLogin('admin@solohustle.test', 'Admin', UserRole::Admin);
        $writers = $this->writers();
        $this->posts($admin, $writers);
        $this->jobs();
        $this->subscribersAndMessages();

        $this->command?->info('Demo content added. Log in at /admin as admin@solohustle.test / password, or /writer as writer@solohustle.test / password.');
    }

    private function demoLogin(string $email, string $name, UserRole $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill(['name' => $user->name ?? $name, 'password' => 'password', 'role' => $role, 'email_verified_at' => now()])->save();

        return $user;
    }

    /**
     * @return Collection<int, User>
     */
    private function writers(): Collection
    {
        $profiles = [
            ['Test Writer', 'writer@solohustle.test', 'Freelance B2B content writer', 'I write practical guides for SaaS teams and the freelancers who work with them.'],
            ['Amara Okafor', 'amara@'.self::DEMO_DOMAIN, 'SEO strategist for small businesses', 'Eight years helping small brands get found in search, now fully remote from Lagos.'],
            ['Lucas Moreau', 'lucas@'.self::DEMO_DOMAIN, 'Remote support team lead', 'I build calm, fast customer support teams across five time zones.'],
            ['Priya Raman', 'priya@'.self::DEMO_DOMAIN, 'Product designer and mentor', 'Designer turned mentor. I help juniors land their first remote design role.'],
        ];

        return collect($profiles)->map(function (array $profile, int $index) {
            [$name, $email, $headline, $bio] = $profile;
            $writer = $this->demoLogin($email, $name, UserRole::Writer);
            $writer->forceFill([
                'headline' => $headline,
                'bio' => $bio,
                'photo' => $this->images->avatar($index + 1),
                'website_url' => 'https://example.com/'.str($name)->slug(),
                'linkedin_url' => 'https://linkedin.com/in/'.str($name)->slug(),
            ])->save();

            return $writer;
        });
    }

    /**
     * @param  Collection<int, User>  $writers
     */
    private function posts(User $admin, Collection $writers): void
    {
        $categories = Category::orderBy('sort')->get();
        $tags = collect(['Remote work', 'Pricing', 'Portfolio', 'Interviews', 'Tools', 'Writing'])
            ->map(fn (string $name) => Tag::firstOrCreate(['slug' => str($name)->slug()], ['name' => $name]));

        $titles = [
            ['How to find your first remote job in 30 days', PostStatus::Published, true],
            ['What should a beginner freelancer charge?', PostStatus::Published, true],
            ['How to write a portfolio piece that wins clients', PostStatus::Published, true],
            ['The remote interview checklist', PostStatus::Published, false],
            ['How to rank a brand-new blog post', PostStatus::Published, false],
            ['Notion vs Trello for solo freelancers', PostStatus::Published, false],
            ['How to say no to a client without losing them', PostStatus::Published, false],
            ['Writing for AI search: a practical guide', PostStatus::Published, false],
            ['How to stay focused working from home', PostStatus::Published, false],
            ['The best free tools for remote writers', PostStatus::Published, false],
            ['How to negotiate a remote salary', PostStatus::Pending, false],
            ['Cold emails that actually get replies', PostStatus::Pending, false],
            ['Building a content calendar that sticks', PostStatus::ChangesRequested, false],
            ['My first year as a virtual assistant', PostStatus::Draft, false],
            ['How to price SEO audits', PostStatus::Draft, false],
            ['Ten ways to get rich quick online', PostStatus::Rejected, false],
        ];

        foreach ($titles as $index => [$title, $status, $featured]) {
            $author = $index % 5 === 0 ? $admin : $writers[$index % $writers->count()];
            $isPublished = $status === PostStatus::Published;

            $post = new Post([
                'author_id' => $author->id,
                'category_id' => $categories[$index % $categories->count()]->id,
                'title' => $title,
                'excerpt' => fake()->sentence(20),
                'body' => $this->articleBody(),
                'cover_image' => $index < 12 ? $this->images->cover($index + 1) : null,
                'cover_alt' => $index < 12 ? 'Abstract shapes in Solo Hustle colours' : null,
                'status' => $status,
                'is_featured' => $featured,
                'quick_answer' => $index % 2 === 0 ? fake()->words(48, true).'.' : null,
                'key_takeaway' => $index % 3 === 0 ? fake()->sentence(14) : null,
                'faqs' => $index % 2 === 0 ? [
                    ['question' => 'How long does this take?', 'answer' => fake()->sentence(18)],
                    ['question' => 'Do I need experience?', 'answer' => fake()->sentence(16)],
                ] : null,
                'sources' => $index % 3 === 0 ? [['title' => 'Example research report', 'url' => 'https://example.com/report']] : null,
            ]);

            $submittedAt = in_array($status, [PostStatus::Pending, PostStatus::ChangesRequested, PostStatus::Rejected], true)
                ? now()->subDays($index - 9)
                : null;

            $post->forceFill([
                'published_at' => $isPublished ? now()->subDays(($index + 1) * 3) : null,
                'submitted_at' => $submittedAt,
                'review_note' => match ($status) {
                    PostStatus::ChangesRequested => 'Great start! Please add a real example in the second section and cite one source.',
                    PostStatus::Rejected => 'Thanks for writing this, but it doesn\'t fit our editorial policy on get-rich-quick content.',
                    default => null,
                },
                'owns_work_confirmed_at' => $submittedAt,
                'originality_confirmed_at' => $submittedAt,
            ])->save();

            $post->tags()->sync($tags->random(2)->pluck('id'));
        }
    }

    private function articleBody(): string
    {
        $headings = ['Why does this matter?', 'Where should you start?', 'What does a good example look like?', 'Which mistakes should you avoid?', 'What should you do next?'];

        return collect($headings)->map(function (string $heading, int $index) {
            $section = "<h2>{$heading}</h2><p>".implode('</p><p>', fake()->paragraphs(2)).'</p>';

            if ($index === 1) {
                $section .= '<ul><li>'.implode('</li><li>', fake()->sentences(4)).'</li></ul>';
            }

            if ($index === 2) {
                $section .= '<h3>A quick example</h3><p>'.fake()->paragraph().'</p>';
            }

            return $section;
        })->implode('');
    }

    private function jobs(): void
    {
        $categories = JobCategory::all();
        $regions = Region::all();
        $titles = ['Content Writer', 'SEO Specialist', 'Customer Support Agent', 'Product Designer', 'Frontend Developer',
            'Email Marketing Manager', 'Virtual Assistant', 'Copy Editor', 'Social Media Manager', 'Technical Writer'];

        foreach (range(1, 30) as $index) {
            $status = $index > 25 ? JobStatus::Closed : JobStatus::Open;
            $postedDaysAgo = ($index * 2) % 45;

            Job::create([
                'title' => $titles[$index % count($titles)],
                'company_name' => fake()->company(),
                'company_url' => 'https://example.com/company-'.$index,
                'company_logo' => $index % 2 === 0 ? $this->images->logo($index) : null,
                'job_category_id' => $categories[$index % $categories->count()]->id,
                'region_id' => $regions[$index % $regions->count()]->id,
                'country_code' => $index % 4 === 0 ? fake()->randomElement(['NG', 'GB', 'US', 'DE', 'KE']) : null,
                'location_note' => $index % 3 === 0 ? 'Remote, overlapping UK hours' : null,
                'work_mode' => [WorkMode::Remote, WorkMode::Remote, WorkMode::Hybrid, WorkMode::Onsite][$index % 4],
                'employment_type' => EmploymentType::cases()[$index % count(EmploymentType::cases())],
                'experience_level' => ExperienceLevel::cases()[$index % count(ExperienceLevel::cases())],
                'salary_text' => $index % 3 === 0 ? '$'.(30 + $index).'–'.(45 + $index).'k a year' : null,
                'summary' => fake()->paragraph(4)."\n\n".fake()->paragraph(3),
                'requirements' => implode("\n", fake()->sentences(4)),
                'apply_url' => 'https://example.com/careers/'.$index,
                'source_name' => 'Company careers page',
                'source_url' => 'https://example.com/careers',
                'posted_at' => today()->subDays($postedDaysAgo),
                // Every fifth open job hasn't been checked for over a month, to show the re-check reminders.
                'last_verified_at' => $index % 5 === 0 ? now()->subDays(40) : now()->subDays($postedDaysAgo % 20),
                'closes_at' => $index % 6 === 0 ? today()->addDays(10 + $index) : null,
                'status' => $status,
                'is_featured' => in_array($index, [3, 11], true),
            ]);
        }
    }

    private function subscribersAndMessages(): void
    {
        foreach (['reader1', 'reader2', 'reader3'] as $index => $name) {
            Subscriber::create(['email' => "{$name}@".self::DEMO_DOMAIN, 'source' => 'homepage'])
                ->forceFill(['confirmed_at' => now()->subDays($index * 4)])->save();
        }

        Subscriber::create(['email' => 'pending@'.self::DEMO_DOMAIN, 'source' => 'article']);
        Subscriber::create(['email' => 'gone@'.self::DEMO_DOMAIN, 'source' => 'homepage'])
            ->forceFill(['confirmed_at' => now()->subMonth(), 'unsubscribed_at' => now()->subWeek()])->save();

        $messages = [
            ['Jordan Lee', 'Broken apply link', 'The apply link on the Copy Editor job goes to a 404 page. Thought you\'d want to know!'],
            ['Sam Patel', 'Partnership idea', 'We run a remote-work newsletter with 20k readers and would love to cross-promote.'],
            ['Chen Wu', 'Writing for you', 'Do you accept articles about freelancing in Asia? I have a few ideas.'],
            ['Maria Silva', 'Thank you', 'Your guide on pricing helped me land my first client. Thank you!'],
        ];

        foreach ($messages as $index => [$name, $subject, $message]) {
            $contact = ContactMessage::create(['name' => $name, 'email' => str($name)->slug().'@'.self::DEMO_DOMAIN, 'subject' => $subject, 'message' => $message]);

            if ($index >= 2) {
                $contact->markRead();
            }
        }
    }
}
