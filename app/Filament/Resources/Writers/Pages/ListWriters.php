<?php

declare(strict_types=1);

namespace App\Filament\Resources\Writers\Pages;

use App\Filament\Resources\Writers\WriterResource;
use Filament\Resources\Pages\ListRecords;

/** All guest writers. */
class ListWriters extends ListRecords
{
    protected static string $resource = WriterResource::class;
}
