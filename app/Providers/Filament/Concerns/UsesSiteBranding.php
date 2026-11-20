<?php

declare(strict_types=1);

namespace App\Providers\Filament\Concerns;

use Filament\Panel;
use Filament\Support\Colors\Color;

/** Gives a Filament panel the site name, logo, favicon and brand colour from Settings. */
trait UsesSiteBranding
{
    protected function applySiteBranding(Panel $panel): Panel
    {
        return $panel
            ->brandName(fn (): string => setting('general.site_name'))
            ->brandLogo(fn (): ?string => media_url(setting('general.logo')))
            ->brandLogoHeight('2.5rem')
            ->favicon(fn (): ?string => media_url(setting('general.favicon')))
            ->colors([
                'primary' => Color::hex('#0750A1'),
            ]);
    }
}
