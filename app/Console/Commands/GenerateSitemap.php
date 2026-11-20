<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\SitemapBuilder;
use Illuminate\Console\Command;

/** Writes public/sitemap.xml. Runs daily from the scheduler, and from the dashboard's maintenance page later. */
class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Rebuild sitemap.xml for search engines';

    public function handle(SitemapBuilder $sitemap): int
    {
        $sitemap->writeTo(public_path('sitemap.xml'));

        $this->info('Sitemap written to '.public_path('sitemap.xml'));

        return self::SUCCESS;
    }
}
