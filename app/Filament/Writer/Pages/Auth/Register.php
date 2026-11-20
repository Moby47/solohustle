<?php

declare(strict_types=1);

namespace App\Filament\Writer\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;
use Throwable;

/** Writer sign-up with a honeypot on top of Filament's built-in throttling. */
class Register extends BaseRegister
{
    use UsesSpamProtection;

    public HoneypotData $extraFields;

    public function mount(): void
    {
        $this->extraFields = new HoneypotData;

        parent::mount();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            View::make('filament.honeypot'),
        ]);
    }

    public function register(): ?RegistrationResponse
    {
        $this->protectAgainstSpam();

        return parent::register();
    }

    /**
     * A mail outage shouldn't block sign-up; the writer can resend the link from the verification screen.
     */
    protected function sendEmailVerificationNotification(Model $user): void
    {
        try {
            parent::sendEmailVerificationNotification($user);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
