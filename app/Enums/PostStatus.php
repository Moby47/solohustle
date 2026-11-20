<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Where an article is in the write → review → publish flow. */
enum PostStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case ChangesRequested = 'changes_requested';
    case Published = 'published';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Awaiting review',
            self::ChangesRequested => 'Changes requested',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::ChangesRequested => 'info',
            self::Published => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Writers may only edit while the post is with them, not while it's under review or after a decision.
     */
    public function isEditableByWriter(): bool
    {
        return in_array($this, [self::Draft, self::ChangesRequested], true);
    }
}
