<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/** A topic label on articles, e.g. "Upwork" or "Pricing". */
#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            $tag->slug = filled($tag->slug) ? $tag->slug : Str::slug($tag->name);
        });
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
