<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a writer their article has been accepted and is live. */
class PostPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Post $post, public bool $withEdits = false) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $siteName = setting('general.site_name');

        $message = (new MailMessage)
            ->subject("Your article is live: {$this->post->title}")
            ->greeting("Congratulations, {$notifiable->name}!")
            ->line("\"{$this->post->title}\" has been accepted and is now published on {$siteName}.");

        if ($this->withEdits) {
            $message->line('Our editor made a few edits before publishing. Have a read to see the final version.');
        }

        return $message
            ->action('Read your article', $this->post->url())
            ->line('Share it with your network, and feel free to use it as a portfolio sample. Thank you for writing with us!');
    }
}
