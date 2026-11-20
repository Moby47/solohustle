<?php

declare(strict_types=1);

return [

    // First admin account, created by EssentialsSeeder on install. Leave the password empty to have one generated.
    'admin_email' => env('ADMIN_EMAIL', 'admin@solohustle.test'),
    'admin_password' => env('ADMIN_PASSWORD'),

    // Top-level addresses used by the app itself, so pages and categories can't take them.
    'reserved_slugs' => [
        'admin', 'api', 'authors', 'build', 'category', 'css', 'email', 'feed', 'filament', 'fonts', 'jobs',
        'js', 'livewire', 'llms', 'login', 'logout', 'password', 'publish', 'register', 'robots', 'rss',
        'search', 'sitemap', 'storage', 'tag', 'tags', 'up', 'uploads', 'vendor', 'writer',
    ],

];
