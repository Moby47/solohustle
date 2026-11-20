<?php

declare(strict_types=1);

namespace App\Filament\Resources\Jobs\Tables;

use App\Enums\JobStatus;
use App\Enums\WorkMode;
use App\Filament\Resources\Jobs\JobActions;
use App\Models\Job;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** The jobs list in the dashboard. */
class JobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['jobCategory', 'region']))
            ->defaultSort('posted_at', 'desc')
            ->columns([
                ImageColumn::make('company_logo')->label('')->disk(config('media.disk'))->imageSize(36),
                TextColumn::make('title')
                    ->label('Job')
                    ->description(fn (Job $record): string => $record->company_name.' · '.$record->jobCategory->name)
                    ->searchable(['title', 'company_name'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('work_mode')
                    ->label('Where')
                    ->formatStateUsing(fn (Job $record): string => $record->work_mode->getLabel().' · '.$record->region->name),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('posted_at')->label('Posted')->date('j M Y')->sortable(),
                TextColumn::make('closes_at')->label('Closes')->date('j M Y')->placeholder('—')->sortable(),
                TextColumn::make('last_verified_at')
                    ->label('Last checked')
                    ->since()
                    ->sortable()
                    ->color(fn (Job $record): ?string => $record->isDueForRecheck() ? 'warning' : null)
                    ->icon(fn (Job $record): ?Heroicon => $record->isDueForRecheck() ? Heroicon::OutlinedExclamationTriangle : null),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('')
                    ->trueColor('warning'),
            ])
            ->filters([
                Filter::make('needs_recheck')
                    ->label('Needs re-check (not checked for '.Job::RECHECK_AFTER_DAYS.'+ days)')
                    ->query(fn (Builder $query) => $query->needsRecheck())
                    ->toggle(),
                SelectFilter::make('status')->options(JobStatus::class),
                SelectFilter::make('jobCategory')->label('Category')->relationship('jobCategory', 'name')->preload(),
                SelectFilter::make('region')->relationship('region', 'name')->preload(),
                SelectFilter::make('work_mode')->label('Work mode')->options(WorkMode::class),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    JobActions::markVerified(),
                    JobActions::close(),
                    JobActions::reopen(),
                    JobActions::duplicate(),
                    JobActions::viewOnSite(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markVerified')
                        ->label('Mark verified')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->action(function (Collection $records): void {
                            $records->each(fn (Job $job) => $job->markVerified());
                            Notification::make()->success()->title('Marked as checked today')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('close')
                        ->label('Close')
                        ->icon(Heroicon::OutlinedLockClosed)
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (Job $job) => $job->close());
                            Notification::make()->success()->title('Jobs closed')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No jobs here')
            ->emptyStateDescription('Add a job you\'ve found on an employer\'s site. Write the summary in your own words.');
    }
}
