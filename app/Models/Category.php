<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostStatus;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A blog section such as Career or Freelancing, served at /{slug}. */
#[Fillable(['name', 'slug', 'description', 'seo_title', 'meta_description', 'sort'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // Renaming a category moves its page and every live article in it, so keep old links working.
        static::updated(function (Category $category) {
            if (! $category->wasChanged('slug')) {
                return;
            }

            $old = $category->getOriginal('slug');

            Redirect::recordMove("/{$old}", "/{$category->slug}");

            $category->posts()->where('status', PostStatus::Published)->pluck('slug')
                ->each(fn (string $slug) => Redirect::recordMove("/{$old}/{$slug}", "/{$category->slug}/{$slug}"));
        });
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function url(): string
    {
        return url('/'.$this->slug);
    }
}
