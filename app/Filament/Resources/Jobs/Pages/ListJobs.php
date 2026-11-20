<?php

declare(strict_types=1);

namespace App\Filament\Resources\Jobs\Pages;

use App\Enums\JobStatus;
use App\Filament\Resources\Jobs\JobResource;
use App\Models\Job;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/** Jobs with quick tabs: open, needing a re-check, closed. */
class ListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New job'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'open';
    }

    public function getTabs(): array
    {
        $recheck = Job::needsRecheck()->count();

        return [
            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', JobStatus::Open)),
            'recheck' => Tab::make('Needs re-check')
                ->badge($recheck ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsRecheck()),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', JobStatus::Closed)),
            'all' => Tab::make('All'),
        ];
    }
}
