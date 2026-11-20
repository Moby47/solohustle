<?php

declare(strict_types=1);

namespace App\Filament\Resources\Jobs\Pages;

use App\Filament\Resources\Jobs\JobActions;
use App\Filament\Resources\Jobs\JobResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/** Edit a job, with its everyday actions at the top. */
class EditJob extends EditRecord
{
    protected static string $resource = JobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            JobActions::viewOnSite(),
            JobActions::markVerified()->after(fn () => $this->refreshFormData(['last_verified_at'])),
            JobActions::close()->after(fn () => $this->refreshFormData(['status'])),
            JobActions::reopen()->after(fn () => $this->refreshFormData(['status'])),
            ActionGroup::make([
                JobActions::duplicate(),
                DeleteAction::make(),
            ]),
        ];
    }
}
