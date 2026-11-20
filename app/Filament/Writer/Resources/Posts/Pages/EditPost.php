<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts\Pages;

use App\Filament\Writer\Resources\Posts\PostResource;
use App\Models\Post;
use App\Support\PostReview;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Edit a draft and send it for review.
 *
 * @property Post $record
 */
class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn (): string => $this->record->url())
                ->openUrlInNewTab(),
            Action::make('submit')
                ->label('Submit for review')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->authorize(fn (): bool => auth()->user()->can('submit', $this->record))
                ->modalHeading('Submit for review?')
                ->modalDescription('Your latest changes are saved first. While the editor reviews it, you can\'t edit the article. You\'ll get an email with their decision.')
                ->schema([
                    Checkbox::make('owns_work')
                        ->label('I wrote this myself and have the right to publish it. It isn\'t owned by a client or covered by a confidentiality agreement (NDA).')
                        ->accepted()
                        ->validationMessages(['accepted' => 'Please confirm this to submit.']),
                    Checkbox::make('original')
                        ->label('This is original work and hasn\'t been published anywhere else.')
                        ->accepted()
                        ->validationMessages(['accepted' => 'Please confirm this to submit.']),
                ])
                ->modalSubmitActionLabel('Submit')
                ->action(fn () => $this->submitForReview()),
            DeleteAction::make()
                ->modalDescription('Your draft is deleted for good.'),
        ];
    }

    private function submitForReview(): void
    {
        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

        $problems = $this->submissionProblems();

        if ($problems !== []) {
            Notification::make()
                ->warning()
                ->title('Almost there')
                ->body('Before submitting: '.implode(' ', $problems))
                ->persistent()
                ->send();

            return;
        }

        app(PostReview::class)->submit($this->record);

        Notification::make()
            ->success()
            ->title('Submitted for review')
            ->body('Thanks! The editor will read it soon and email you their decision.')
            ->send();

        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
    }

    /**
     * What's missing for a reviewable submission, in plain words.
     *
     * @return list<string>
     */
    private function submissionProblems(): array
    {
        $problems = [];
        $minimumWords = config('site.min_submission_words');
        $words = str_word_count(strip_tags((string) $this->record->body));

        if (blank($this->record->excerpt)) {
            $problems[] = 'Add a short summary.';
        }

        if ($words < $minimumWords) {
            $problems[] = "The article text needs at least {$minimumWords} words (it has {$words}).";
        }

        return $problems;
    }
}
