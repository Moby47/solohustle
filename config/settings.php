<?php

declare(strict_types=1);

/*
 * Default values for the dashboard's Site settings. Anything the owner saves overrides these.
 * Top-level keys are the settings tabs; each is stored as one row in the settings table.
 */

return [

    // Stored encrypted and never shown back in full.
    'secrets' => [
        'integrations.smtp_password',
        'integrations.brevo_api_key',
    ],

    'defaults' => [

        'general' => [
            'site_name' => 'Solo Hustle',
            'tagline' => 'Work. Write. Build.',
            'logo' => null,
            'favicon' => null,
            'contact_email' => 'solohustlehub@gmail.com',
            'contact_recipient' => 'solohustlehub@gmail.com',
        ],

        'homepage' => [
            'hero' => [
                'visible' => true,
                'headline' => 'Remote jobs, practical guides and a place to publish your work.',
                'text' => 'Curated by hand. Written to be useful.',
                'primary_label' => 'Browse jobs',
                'primary_link' => '/jobs',
                'secondary_label' => 'Publish with us',
                'secondary_link' => '/publish',
            ],
            'jobs' => [
                'visible' => true,
                'title' => 'Latest remote jobs',
                'count' => 6,
            ],
            'guides' => [
                'visible' => true,
                'title' => 'Featured guides',
            ],
            'creator' => [
                'visible' => true,
                'title' => 'Publish your work',
                'text' => 'Write a practical guide, get it reviewed by an editor and use it as a portfolio sample.',
            ],
            'articles' => [
                'visible' => true,
                'title' => 'Latest articles',
                'count' => 6,
            ],
            'resource' => [
                'visible' => false,
                'title' => '',
                'text' => '',
                'image' => null,
                'link_label' => '',
                'link' => null,
            ],
            'newsletter' => [
                'visible' => true,
                'title' => 'New jobs and guides, once a week',
                'text' => 'One short email. No spam. Unsubscribe any time.',
            ],
        ],

        'navigation' => [
            'header' => [
                ['label' => 'Jobs', 'link' => '/jobs'],
                ['label' => 'Career', 'link' => '/career'],
                ['label' => 'Freelancing', 'link' => '/freelancing'],
                ['label' => 'SEO & Content', 'link' => '/seo-content'],
                ['label' => 'Reviews & Guides', 'link' => '/reviews-guides'],
            ],
            'cta_label' => 'Publish with us',
            'cta_link' => '/publish',
            'footer' => [
                [
                    'title' => 'Explore',
                    'links' => [
                        ['label' => 'All jobs', 'link' => '/jobs'],
                        ['label' => 'Remote jobs', 'link' => '/jobs/remote'],
                        ['label' => 'Search', 'link' => '/search'],
                    ],
                ],
                [
                    'title' => 'Solo Hustle',
                    'links' => [
                        ['label' => 'About', 'link' => '/about'],
                        ['label' => 'Contact', 'link' => '/contact'],
                        ['label' => 'Publish with us', 'link' => '/publish'],
                        ['label' => 'Advertise', 'link' => '/advertise'],
                    ],
                ],
                [
                    'title' => 'Policies',
                    'links' => [
                        ['label' => 'Editorial Policy', 'link' => '/editorial-policy'],
                        ['label' => 'Creator Guidelines', 'link' => '/creator-guidelines'],
                        ['label' => 'Privacy Policy', 'link' => '/privacy-policy'],
                        ['label' => 'Terms', 'link' => '/terms'],
                    ],
                ],
            ],
        ],

        'social' => [
            'links' => [
                ['platform' => 'x', 'url' => 'https://x.com/solohustle_test'],
                ['platform' => 'facebook', 'url' => 'https://facebook.com/solohustle.test'],
                ['platform' => 'linkedin', 'url' => 'https://linkedin.com/company/solohustle-test'],
                ['platform' => 'instagram', 'url' => 'https://instagram.com/solohustle.test'],
            ],
        ],

        'seo' => [
            'default_meta_description' => 'Hand-picked remote jobs, practical career and freelancing guides, and a place for writers to publish their work.',
            'default_share_image' => null,
            'title_suffix' => 'Solo Hustle',
            'discourage_search_engines' => true,
        ],

        'article' => [
            'mid_cta_text' => 'Looking for remote work? Browse hand-picked jobs, each checked by a real person.',
            'mid_cta_label' => 'Browse jobs',
            'mid_cta_link' => '/jobs',
            'newsletter_text' => 'Get new guides and jobs by email. One short email a week.',
        ],

        'integrations' => [
            'smtp_host' => null,
            'smtp_port' => 587,
            'smtp_username' => null,
            'smtp_password' => null,
            'mail_from_address' => null,
            'mail_from_name' => null,
            'brevo_api_key' => null,
            'brevo_list_id' => null,
            'ga4_id' => null,
            'search_console_code' => null,
        ],

    ],

];
