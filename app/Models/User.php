<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Support\ImageProcessor;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/** An account: the site owner (admin) or a guest writer. */
#[Fillable(['name', 'email', 'password', 'headline', 'bio', 'photo', 'website_url', 'linkedin_url', 'x_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => 'writer',
    ];

    protected static function booted(): void
    {
        // The slug is set once so author page URLs stay stable when a writer renames themselves.
        static::creating(function (User $user) {
            $user->slug ??= static::uniqueSlugFor($user->name);
        });

        static::updated(function (User $user) {
            if ($user->wasChanged('photo')) {
                app(ImageProcessor::class)->delete($user->getOriginal('photo'));
            }
        });
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name) ?: 'writer';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isWriter(): bool
    {
        return $this->role === UserRole::Writer;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Author pages exist only for people in good standing who have something published.
     */
    public function hasAuthorPage(): bool
    {
        return ! $this->isSuspended() && $this->posts()->published()->exists();
    }

    public function authorUrl(): string
    {
        return url('/authors/'.$this->slug);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isAdmin(),
            'writer' => $this->isWriter() && ! $this->isSuspended(),
            default => false,
        };
    }
}
