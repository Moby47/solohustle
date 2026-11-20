<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use App\Notifications\PostChangesRequested;
use App\Notifications\PostPublished;
use App\Notifications\PostRejected;
use App\Notifications\PostSubmittedForReview;
use Illuminate\Notifications\Notification;
use Throwable;

/** The write → review → publish flow. Every step that involves someone else sends them an email. */
final class PostReview
{
    /**
     * A writer sends their article to the owner, confirming it is theirs to publish and original.
     */
    public function submit(Post $post): void
    {
        $post->forceFill([
            'status' => PostStatus::Pending,
            'submitted_at' => now(),
            'owns_work_confirmed_at' => now(),
            'originality_confirmed_at' => now(),
        ])->save();

        User::where('role', UserRole::Admin)->get()
            ->each(fn (User $admin) => $this->notify($admin, new PostSubmittedForReview($post)));
    }

    public function accept(Post $post, User $reviewer, bool $withEdits = false): void
    {
        $post->forceFill([
            'status' => PostStatus::Published,
            'published_at' => now(),
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => null,
        ])->save();

        $this->notifyAuthor($post, $reviewer, new PostPublished($post, $withEdits));
    }

    public function requestChanges(Post $post, User $reviewer, string $note): void
    {
        $this->decide($post, $reviewer, PostStatus::ChangesRequested, $note);

        $this->notifyAuthor($post, $reviewer, new PostChangesRequested($post));
    }

    public function reject(Post $post, User $reviewer, string $note): void
    {
        $this->decide($post, $reviewer, PostStatus::Rejected, $note);

        $this->notifyAuthor($post, $reviewer, new PostRejected($post));
    }

    private function decide(Post $post, User $reviewer, PostStatus $status, string $note): void
    {
        $post->forceFill([
            'status' => $status,
            'review_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();
    }

    private function notifyAuthor(Post $post, User $reviewer, Notification $notification): void
    {
        // The owner doesn't need an email about reviewing their own article.
        if ($post->author_id === $reviewer->id) {
            return;
        }

        $this->notify($post->author, $notification);
    }

    /**
     * Emails are queued; if even queuing fails, log it and let the review go ahead.
     */
    private function notify(User $user, Notification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
