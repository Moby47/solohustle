<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a writer their article won't be published, with the editor's reason. */
class PostRejected extends Notification implements ShouldQueue
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
            ->subject("About your article: {$this->post->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Thank you for submitting \"{$this->post->title}\". Unfortunately we won't be publishing it. Here's why:")
            ->line('> '.str_replace("\n", "\n> ", (string) $this->post->review_note))
            ->line('You are very welcome to write something new and submit it.')
            ->action('Go to your articles', url('/writer'));
    }
}
