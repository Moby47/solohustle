<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A message sent through the contact form, shown in the owner's Inbox. */
#[Fillable(['name', 'email', 'subject', 'message'])]
class ContactMessage extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<ContactMessage>  $query
     */
    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markRead(): void
    {
        if (! $this->isRead()) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function markUnread(): void
    {
        $this->forceFill(['read_at' => null])->save();
    }

    /**
     * A mailto: link that opens a reply in the owner's email app.
     */
    public function replyUrl(): string
    {
        return 'mailto:'.$this->email.'?'.http_build_query(['subject' => 'Re: '.$this->subject], '', '&', PHP_QUERY_RFC3986);
    }
}
