<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\WorkMode;
use App\Models\Job;

/** Google's JobPosting structured data for a job page. Closed jobs get none, so Google drops them. */
final class JobPostingSchema
{
    /**
     * @return array<string, mixed>|null
     */
    public static function for(Job $job): ?array
    {
        if (! $job->isOpen()) {
            return null;
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->title,
            'description' => self::description($job),
            'datePosted' => $job->posted_at->toDateString(),
            'validThrough' => $job->closes_at?->copy()->endOfDay()->toIso8601String(),
            'employmentType' => $job->employment_type->schemaValue(),
            'hiringOrganization' => array_filter([
                '@type' => 'Organization',
                'name' => $job->company_name,
                'sameAs' => $job->company_url,
                'logo' => $job->logoUrl(),
            ]),
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => setting('general.site_name'),
                'value' => (string) $job->getKey(),
            ],
            'url' => $job->url(),
            'directApply' => false,
            ...self::location($job),
        ], fn (mixed $value): bool => $value !== null);
    }

    private static function description(Job $job): string
    {
        $html = collect(preg_split('/\R{2,}/', trim($job->summary)))
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph))).'</p>')
            ->implode('');

        $requirements = $job->requirementsList();

        if ($requirements !== []) {
            $html .= '<p>Requirements:</p><ul>'.collect($requirements)->map(fn (string $item): string => '<li>'.e($item).'</li>')->implode('').'</ul>';
        }

        return $html;
    }

    /**
     * Remote jobs say where applicants may live; on-site and hybrid jobs give the workplace country.
     *
     * @return array<string, mixed>
     */
    private static function location(Job $job): array
    {
        if ($job->work_mode === WorkMode::Remote) {
            $requirement = match (true) {
                $job->country_code !== null => ['@type' => 'Country', 'name' => $job->countryName()],
                ! $job->region->is_worldwide => ['@type' => 'AdministrativeArea', 'name' => $job->region->name],
                default => null,
            };

            return array_filter([
                'jobLocationType' => 'TELECOMMUTE',
                'applicantLocationRequirements' => $requirement,
            ]);
        }

        if ($job->country_code === null) {
            return [];
        }

        return [
            'jobLocation' => [
                '@type' => 'Place',
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job->location_note,
                    'addressCountry' => $job->country_code,
                ]),
            ],
        ];
    }
}
