<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Jobs\JobResource;
use App\Filament\Resources\Posts\PostResource;
use Filament\Widgets\Widget;

/** One-click shortcuts for the two things the owner adds most. */
class QuickActions extends Widget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.quick-actions';

    protected int|string|array $columnSpan = 1;

    /**
     * @return array<string, string>
     */
    protected function getViewData(): array
    {
        return [
            'newJobUrl' => JobResource::getUrl('create'),
            'newArticleUrl' => PostResource::getUrl('create'),
            'siteUrl' => url('/'),
        ];
    }
}
