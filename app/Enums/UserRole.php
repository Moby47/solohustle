<?php

declare(strict_types=1);

namespace App\Enums;

/** The kind of account a user has, which decides which panel they can use. */
enum UserRole: string
{
    case Admin = 'admin';
    case Writer = 'writer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Writer => 'Writer',
        };
    }
}
