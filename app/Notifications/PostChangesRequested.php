<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a writer the editor wants changes, with the editor's note. */
class PostChangesRequested extends Notification implements ShouldQueue
{
    use Queueable;

    /** If the record was deleted before the email went out, drop the email instead of failing. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Post $post) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("A few changes to: {$this->post->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Thanks for submitting \"{$this->post->title}\". It's nearly there. Our editor has asked for a few changes:")
            ->line('> '.str_replace("\n", "\n> ", (string) $this->post->review_note))
            ->line('Make the changes, then submit the article for review again.')
            ->action('Edit your article', $this->post->writerUrl());
    }
}
