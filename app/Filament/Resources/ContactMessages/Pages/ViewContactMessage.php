<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Read a message. Opening it marks it as read.
 *
 * @property ContactMessage $record
 */
class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->markRead();
    }

    protected function getHeaderActions(): array
    {
        return [
            ContactMessageResource::replyAction(),
            // Back to the Inbox, or reopening this page would mark it read again straight away.
            ContactMessageResource::markUnreadAction()
                ->after(fn () => $this->redirect(ContactMessageResource::getUrl())),
            DeleteAction::make(),
        ];
    }
}
