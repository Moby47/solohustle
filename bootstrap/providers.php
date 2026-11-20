<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\WriterPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    WriterPanelProvider::class,
];
