<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/** Who may do what with articles. The owner can do everything; writers only touch their own unsent work. */
class PostPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isActiveWriter($user);
    }

    public function view(User $user, Post $post): bool
    {
        return $this->isActiveWriter($user) && $post->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isActiveWriter($user);
    }

    public function update(User $user, Post $post): bool
    {
        return $this->view($user, $post) && $post->isEditableByWriter();
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function submit(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    /**
     * Only the owner publishes (granted in before()).
     */
    public function publish(User $user): bool
    {
        return false;
    }

    private function isActiveWriter(User $user): bool
    {
        return $user->isWriter() && ! $user->isSuspended();
    }
}
