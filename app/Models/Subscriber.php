<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/** A newsletter subscriber. Active only after confirming by email (double opt-in) and until they unsubscribe. */
#[Fillable(['email', 'name', 'source'])]
class Subscriber extends Model
{
    /** How long a confirmation link stays valid. */
    public const CONFIRM_LINK_DAYS = 7;

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'brevo_synced_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Subscriber>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function isActive(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->unsubscribed_at !== null => 'Unsubscribed',
            $this->confirmed_at !== null => 'Subscribed',
            default => 'Waiting for confirmation',
        };
    }

    public function confirmUrl(): string
    {
        return URL::temporarySignedRoute('newsletter.confirm', now()->addDays(self::CONFIRM_LINK_DAYS), ['subscriber' => $this->getKey()]);
    }

    public function unsubscribeUrl(): string
    {
        return URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $this->getKey()]);
    }
}
