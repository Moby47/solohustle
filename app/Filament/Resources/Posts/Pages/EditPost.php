<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Support\PostReview;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Edit an article. For submissions awaiting review it also offers the four review decisions.
 *
 * @property Post $record
 */
class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish as is')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->isAwaitingReview())
                ->requiresConfirmation()
                ->modalHeading('Publish this article?')
                ->modalDescription('It goes live straight away and the writer gets an email. Unsaved edits on this screen are not included.')
                ->modalSubmitActionLabel('Publish')
                ->action(function (): void {
                    app(PostReview::class)->accept($this->record, auth()->user());
                    $this->afterReview('Published. The writer has been emailed.');
                }),
            Action::make('publishWithEdits')
                ->label('Save my edits & publish')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('success')
                ->outlined()
                ->visible(fn (): bool => $this->isAwaitingReview())
                ->requiresConfirmation()
                ->modalHeading('Save your edits and publish?')
                ->modalDescription('Your changes are saved, the article goes live, and the writer is told you made some edits.')
                ->modalSubmitActionLabel('Save & publish')
                ->action(function (): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    app(PostReview::class)->accept($this->record, auth()->user(), withEdits: true);
                    $this->afterReview('Saved and published. The writer has been emailed.');
                }),
            ActionGroup::make([
                Action::make('requestChanges')
                    ->label('Ask for changes')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('info')
                    ->modalHeading('Ask the writer for changes')
                    ->modalDescription('The writer gets your note by email and can edit and resubmit the article.')
                    ->schema([
                        Textarea::make('note')
                            ->label('What should they change?')
                            ->required()
                            ->rows(6)
                            ->maxLength(3000),
                    ])
                    ->modalSubmitActionLabel('Send to writer')
                    ->action(function (array $data): void {
                        app(PostReview::class)->requestChanges($this->record, auth()->user(), $data['note']);
                        $this->afterReview('Sent back to the writer with your note.');
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->modalHeading('Reject this article?')
                    ->modalDescription('This is final: the writer can\'t edit or resubmit this article. They get your reason by email.')
                    ->schema([
                        Textarea::make('note')
                            ->label('Why isn\'t it being published?')
                            ->helperText('Be kind and specific. It helps them write something better next time.')
                            ->required()
                            ->rows(6)
                            ->maxLength(3000),
                    ])
                    ->modalSubmitActionLabel('Reject article')
                    ->action(function (array $data): void {
                        app(PostReview::class)->reject($this->record, auth()->user(), $data['note']);
                        $this->afterReview('Rejected. The writer has been emailed.');
                    }),
            ])
                ->label('Other decisions')
                ->button()
                ->color('gray')
                ->visible(fn (): bool => $this->isAwaitingReview()),
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->isAwaitingReview()),
        ];
    }

    private function isAwaitingReview(): bool
    {
        return $this->record->status === PostStatus::Pending;
    }

    private function afterReview(string $message): void
    {
        $this->refreshFormData(['status', 'published_at']);

        Notification::make()->success()->title($message)->send();
    }
}
