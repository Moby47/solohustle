<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** How much experience the employer is looking for. */
enum ExperienceLevel: string implements HasLabel
{
    case Entry = 'entry';
    case Mid = 'mid';
    case Senior = 'senior';
    case Any = 'any';

    public function getLabel(): string
    {
        return match ($this) {
            self::Entry => 'Entry level',
            self::Mid => 'Mid level',
            self::Senior => 'Senior',
            self::Any => 'Any level',
        };
    }
}
