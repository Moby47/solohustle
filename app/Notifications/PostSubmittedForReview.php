<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells the owner a guest writer has sent an article for review. */
class PostSubmittedForReview extends Notification implements ShouldQueue
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
            ->subject("New article to review: {$this->post->title}")
            ->greeting('A new article is waiting for you')
            ->line("**{$this->post->author->name}** has submitted \"{$this->post->title}\" for review.")
            ->line('They have confirmed it is their own original work and theirs to publish.')
            ->action('Review the article', PostResource::getUrl('edit', ['record' => $this->post], panel: 'admin'));
    }
}
