<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/** robots.txt, built from Site settings: the "discourage search engines" switch and the AI crawler switches. */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = setting('seo.discourage_search_engines')
            ? ['# The site is not launched yet.', 'User-agent: *', 'Disallow: /']
            : $this->launchedRules();

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * @return list<string>
     */
    private function launchedRules(): array
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /writer',
            'Disallow: /search',
            'Disallow: /newsletter/',
        ];

        foreach (config('settings.ai_crawlers') as $key => $userAgent) {
            if (! setting("ai.crawlers.{$key}", true)) {
                array_push($lines, '', "User-agent: {$userAgent}", 'Disallow: /');
            }
        }

        array_push($lines, '', 'Sitemap: '.url('/sitemap.xml'));

        return $lines;
    }
}
