<?php

declare(strict_types=1);

namespace App\Filament\Resources\Jobs;

use App\Models\Job;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** The everyday job actions, shared by the jobs list and the job editor. */
final class JobActions
{
    public static function markVerified(): Action
    {
        return Action::make('markVerified')
            ->label('Mark verified')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn (Job $record): bool => $record->isOpen())
            ->tooltip('You checked the employer\'s page and the job is still open')
            ->action(function (Job $record): void {
                $record->markVerified();
                Notification::make()->success()->title('Marked as checked today')->send();
            });
    }

    public static function close(): Action
    {
        return Action::make('close')
            ->label('Close')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('gray')
            ->visible(fn (Job $record): bool => $record->isOpen())
            ->requiresConfirmation()
            ->modalHeading('Close this job?')
            ->modalDescription('It stays viewable with a "Closed" banner and no apply button, and is hidden from job lists. You can reopen it any time.')
            ->modalSubmitActionLabel('Close job')
            ->action(function (Job $record): void {
                $record->close();
                Notification::make()->success()->title('Job closed')->send();
            });
    }

    public static function reopen(): Action
    {
        return Action::make('reopen')
            ->label('Reopen')
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('success')
            ->visible(fn (Job $record): bool => ! $record->isOpen())
            ->requiresConfirmation()
            ->modalHeading('Reopen this job?')
            ->modalDescription(fn (Job $record): string => $record->closes_at?->isPast()
                ? 'Its closing date has passed, so remember to update or clear it, or it will close again tonight.'
                : 'It goes back into the job lists straight away.')
            ->modalSubmitActionLabel('Reopen job')
            ->action(function (Job $record): void {
                $record->reopen();
                Notification::make()->success()->title('Job reopened')->send();
            });
    }

    public static function duplicate(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicate')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Duplicate this job?')
            ->modalDescription('Creates an open copy dated today, so you can quickly add a similar role. You\'ll go straight to the copy to edit it.')
            ->modalSubmitActionLabel('Duplicate')
            ->action(function (Job $record, Action $action): void {
                $copy = $record->duplicate();
                Notification::make()->success()->title('Copy created. Check the details and save.')->send();
                $action->redirect(JobResource::getUrl('edit', ['record' => $copy]));
            });
    }

    public static function viewOnSite(): Action
    {
        return Action::make('viewOnSite')
            ->label('View on site')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (Job $record): string => $record->url())
            ->openUrlInNewTab();
    }
}
