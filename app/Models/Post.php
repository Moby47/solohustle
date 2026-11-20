<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PostStatus;
use App\Support\ImageProcessor;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/** An article, written by the owner or a guest writer. */
#[Fillable([
    'author_id', 'category_id', 'title', 'slug', 'excerpt', 'body', 'cover_image', 'cover_alt', 'status',
    'published_at', 'key_takeaway', 'quick_answer', 'faqs', 'sources', 'is_featured', 'seo_title', 'meta_description',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /** Cover sizes: the full image is 16:9; narrower copies are made for phones and cards. */
    public const COVER_WIDTH = 1600;

    public const COVER_HEIGHT = 900;

    public const COVER_SMALLER_WIDTHS = [960, 480];

    /** Fields readers see; changing them on a live article moves its "Updated" date. */
    private const CONTENT_FIELDS = ['title', 'excerpt', 'body', 'key_takeaway', 'quick_answer', 'faqs', 'sources', 'cover_image'];

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            $post->slug = filled($post->slug) ? $post->slug : static::uniqueSlugFor($post->title);
        });

        static::saving(function (Post $post) {
            $post->reading_minutes = $post->calculateReadingMinutes();

            if ($post->status === PostStatus::Published) {
                $post->published_at ??= now();

                $wasLive = $post->exists && $post->getOriginal('status') === PostStatus::Published;

                if ($wasLive && $post->isDirty(self::CONTENT_FIELDS)) {
                    $post->content_updated_at = now();
                }
            }
        });

        static::updated(function (Post $post) {
            $wasLive = $post->getOriginal('status') === PostStatus::Published;

            if ($wasLive && $post->wasChanged(['slug', 'category_id'])) {
                $oldCategorySlug = Category::whereKey($post->getOriginal('category_id'))->value('slug');
                $post->unsetRelation('category');

                Redirect::recordMove("/{$oldCategorySlug}/{$post->getOriginal('slug')}", $post->path());
            }

            if ($post->wasChanged('cover_image')) {
                app(ImageProcessor::class)->delete($post->getOriginal('cover_image'));
            }
        });

        static::deleted(function (Post $post) {
            app(ImageProcessor::class)->delete($post->cover_image);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'faqs' => 'array',
            'sources' => 'array',
            'is_featured' => 'boolean',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
            'content_updated_at' => 'datetime',
            'owns_work_confirmed_at' => 'datetime',
            'originality_confirmed_at' => 'datetime',
        ];
    }

    public static function uniqueSlugFor(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published)->where('published_at', '<=', now());
    }

    public function isEditableByWriter(): bool
    {
        return $this->status->isEditableByWriter();
    }

    public function path(): string
    {
        return '/'.$this->category->slug.'/'.$this->slug;
    }

    public function url(): string
    {
        return url($this->path());
    }

    /**
     * Where the writer manages this article: the editor while it's with them, otherwise the read-only view.
     */
    public function writerUrl(): string
    {
        return url('/writer/posts/'.$this->getKey().($this->isEditableByWriter() ? '/edit' : ''));
    }

    /**
     * Cover URL at full size, or at one of the narrower widths.
     */
    public function coverUrl(?int $width = null): ?string
    {
        if (blank($this->cover_image)) {
            return null;
        }

        $path = $width === null ? $this->cover_image : Str::beforeLast($this->cover_image, '.webp')."-{$width}.webp";

        return media_url($path);
    }

    /**
     * The date readers see as "Updated": the last real content change, or the publish date.
     */
    public function displayUpdatedAt(): ?\DateTimeInterface
    {
        return $this->content_updated_at ?? $this->published_at;
    }

    private function calculateReadingMinutes(): int
    {
        $words = str_word_count(strip_tags((string) $this->body));

        return max(1, (int) ceil($words / 200));
    }
}
