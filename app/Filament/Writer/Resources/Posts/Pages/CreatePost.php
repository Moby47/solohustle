<?php

declare(strict_types=1);

namespace App\Filament\Writer\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Writer\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;

/** Start a new draft. The author and status are always set here, never taken from the form. */
class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'author_id' => auth()->id(),
            'status' => PostStatus::Draft,
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Draft saved. Submit it for review when it\'s ready.';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
