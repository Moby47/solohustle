<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Post;

/** schema.org data that helps Google and AI assistants understand the site, its articles and their authors. */
final class StructuredData
{
    /**
     * The site itself: who publishes it and how to search it. Output on every page.
     *
     * @return array<string, mixed>
     */
    public static function site(): array
    {
        $siteUrl = url('/');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'Organization',
                    '@id' => $siteUrl.'#organization',
                    'name' => setting('general.site_name'),
                    'url' => $siteUrl,
                    'logo' => media_url(setting('general.logo')),
                    'sameAs' => collect(setting('social.links', []))->pluck('url')->values()->all() ?: null,
                ]),
                [
                    '@type' => 'WebSite',
                    '@id' => $siteUrl.'#website',
                    'name' => setting('general.site_name'),
                    'url' => $siteUrl,
                    'publisher' => ['@id' => $siteUrl.'#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, ?string>  $items  label => url; the last item is the current page
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        $position = 0;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect(['Home' => url('/'), ...$items])
                ->map(fn (?string $url, string $label) => array_filter([
                    '@type' => 'ListItem',
                    'position' => ++$position,
                    'name' => $label,
                    'item' => $url ?? url()->current(),
                ]))
                ->values()
                ->all(),
        ];
    }

    /**
     * dateModified uses the same date the page shows as "Updated" (or the publish date when there's none).
     *
     * @return array<string, mixed>
     */
    public static function article(Post $post, bool $authorHasPage): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->meta_description ?: $post->excerpt,
            'image' => $post->coverUrl(),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->displayUpdatedAt()?->toIso8601String(),
            'author' => array_filter([
                '@type' => 'Person',
                'name' => $post->author->name,
                'url' => $authorHasPage ? $post->author->authorUrl() : null,
            ]),
            'publisher' => ['@id' => url('/').'#organization'],
            'mainEntityOfPage' => $post->url(),
            'articleSection' => $post->category->name,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function faq(Post $post): ?array
    {
        if (empty($post->faqs)) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($post->faqs)->map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
            ])->values()->all(),
        ];
    }
}
