<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use XMLWriter;

/** Builds sitemap.xml: every public, indexable page with its last-changed date. Closed jobs and drafts are left out. */
final class SitemapBuilder
{
    public function build(): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($this->entries() as [$url, $lastModified]) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url);

            if ($lastModified !== null) {
                $xml->writeElement('lastmod', $lastModified->format('Y-m-d'));
            }

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    public function writeTo(string $path): void
    {
        file_put_contents($path, $this->build());
    }

    /**
     * @return iterable<array{string, ?DateTimeInterface}>
     */
    private function entries(): iterable
    {
        $latestPost = Post::published()->max('published_at');
        $latestJob = Job::open()->max('updated_at');

        yield [url('/'), $this->date($latestPost ?? $latestJob)];
        yield [url('/jobs'), $this->date($latestJob)];
        yield [url('/jobs/remote'), $this->date($latestJob)];
        yield [url('/publish'), null];

        foreach (JobCategory::orderBy('sort')->get() as $jobCategory) {
            yield [$jobCategory->url(), null];
        }

        foreach (Job::open()->with('jobCategory')->latest('posted_at')->get() as $job) {
            yield [$job->url(), $job->updated_at];
        }

        foreach (Page::published()->orderBy('slug')->get() as $page) {
            yield [$page->url(), $page->updated_at];
        }

        foreach (Category::orderBy('sort')->get() as $category) {
            yield [$category->url(), null];
        }

        foreach (Post::published()->with('category')->latest('published_at')->get() as $post) {
            yield [$post->url(), $post->displayUpdatedAt()];
        }

        $authors = User::whereNull('suspended_at')->whereHas('posts', fn ($query) => $query->published())->orderBy('slug')->get();

        foreach ($authors as $author) {
            yield [$author->authorUrl(), null];
        }
    }

    private function date(mixed $value): ?DateTimeInterface
    {
        return $value === null ? null : Carbon::parse($value);
    }
}
