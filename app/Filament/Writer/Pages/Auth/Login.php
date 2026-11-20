<?php

declare(strict_types=1);

namespace App\Filament\Writer\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

/** Writer login that tells a suspended writer why they can't sign in. */
class Login extends BaseLogin
{
    /**
     * Only reached after the password has been checked, so the message doesn't reveal which emails exist.
     */
    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        if ($user instanceof User && $user->isSuspended()) {
            throw ValidationException::withMessages([
                'data.email' => 'This account has been suspended. Please contact us if you think this is a mistake.',
            ]);
        }

        return parent::isUserAllowedToAccessPanel($user);
    }
}
