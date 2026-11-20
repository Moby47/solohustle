<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Writer\Pages\Auth\EditProfile;
use App\Filament\Writer\Pages\Auth\Login;
use App\Filament\Writer\Pages\Auth\Register;
use App\Filament\Writer\Widgets\WriterStats;
use App\Providers\Filament\Concerns\UsesSiteBranding;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** The guest writers' area at /writer: sign up, verify email, manage profile and (later) posts. */
class WriterPanelProvider extends PanelProvider
{
    use UsesSiteBranding;

    public function panel(Panel $panel): Panel
    {
        return $this->applySiteBranding($panel)
            ->id('writer')
            ->path('writer')
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->profile(EditProfile::class, isSimple: false)
            ->discoverResources(in: app_path('Filament/Writer/Resources'), for: 'App\Filament\Writer\Resources')
            ->discoverPages(in: app_path('Filament/Writer/Pages'), for: 'App\Filament\Writer\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                WriterStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
